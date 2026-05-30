import { Centrifuge } from 'centrifuge';
import { useMainStore } from '@/stores/main'
import { reactive } from 'vue'
import { useOrderStatusStore } from '@/stores/orderStatus'
import { useTaskStatusStore } from '@/stores/taskStatus'
import { useCourierStatusStore } from '@/stores/courierStatus'

export default {
  install(app, { url }) {
    let centrifuge = null;
    let isConnected = false;
    let isConnecting = false;
    const subscriptions = new Map();
    const state = reactive({
      connected: false,
      lastError: '',
      lastDisconnectedCode: null,
    })

    const connect = async () => {
      if (isConnected || isConnecting || centrifuge) return;

      isConnecting = true;

      try {
        const res = await fetch('/api/centrifugo/connection-token', {
          credentials: 'same-origin',
        });

        if (!res.ok) throw new Error('Failed to get connection token');
        const { token } = await res.json();

        centrifuge = new Centrifuge(url, {
          token,
          getToken: async () => {
            const r = await fetch('/api/centrifugo/connection-token');
            return (await r.json()).token;
          },
        });

        const ensureChannelSubscription = (channel, entry) => {
          if (!centrifuge || entry.subscription) {
            return;
          }

          let sub = centrifuge.getSubscription(channel);
          if (!sub) {
            sub = centrifuge.newSubscription(channel);
          }

          const publicationHandler = (ctx) => {
            entry.callbacks.forEach((callback) => callback(ctx.data));
          };

          sub.on('publication', publicationHandler);
          sub.subscribe();

          entry.subscription = sub;
          entry.publicationHandler = publicationHandler;
        };

        centrifuge.on('connected', () => {
          console.log('Centrifugo connected');
          isConnected = true;
          isConnecting = false;
          state.connected = true
          state.lastError = ''
          state.lastDisconnectedCode = null
        });

        centrifuge.on('disconnected', (ctx) => {
          console.log('Centrifugo disconnected', ctx);
          isConnected = false;
          isConnecting = false;
          state.connected = false
          state.lastDisconnectedCode = ctx?.code ?? null
          subscriptions.forEach((entry) => {
            entry.subscription = null
            entry.publicationHandler = null
          })
        });

        centrifuge.on('error', (err) => {
          console.error('Centrifugo error:', err);
          state.lastError = err?.message || err?.error?.message || JSON.stringify(err) || 'Unknown Centrifugo error'
        });

        subscriptions.forEach((entry, channel) => {
          ensureChannelSubscription(channel, entry);
        });

        centrifuge.connect();
      } catch (error) {
        console.error('Failed to connect to Centrifugo:', error);
        isConnecting = false;
        state.connected = false
        state.lastError = error?.message || 'Failed to connect to Centrifugo'
      }
    };

    const disconnect = () => {
      if (centrifuge) {
        centrifuge.disconnect();
        centrifuge = null;
        isConnected = false;
        isConnecting = false;
        state.connected = false
        subscriptions.forEach((entry) => {
          entry.subscription = null
          entry.publicationHandler = null
        })
        console.log('Centrifugo disconnected');
      }
    };

    const normalizeChannel = (channel) => (typeof channel === 'string' ? channel.trim() : channel)

    const subscribe = (channel, callback) => {
      const normalizedChannel = normalizeChannel(channel)
      if (!normalizedChannel) {
        return { unsubscribe: () => {} }
      }

      if (!subscriptions.has(normalizedChannel)) {
        subscriptions.set(normalizedChannel, {
          callbacks: new Set(),
          subscription: null,
          publicationHandler: null,
        })
      }

      const entry = subscriptions.get(normalizedChannel)
      entry.callbacks.add(callback)

      if (centrifuge && !entry.subscription) {
        let sub = centrifuge.getSubscription(normalizedChannel)
        if (!sub) {
          sub = centrifuge.newSubscription(normalizedChannel)
        }

        const publicationHandler = (ctx) => {
          entry.callbacks.forEach((handler) => handler(ctx.data))
        }

        sub.on('publication', publicationHandler)
        sub.subscribe()
        entry.subscription = sub
        entry.publicationHandler = publicationHandler
      }

      return {
        unsubscribe: () => {
          entry.callbacks.delete(callback)

          if (!entry.callbacks.size) {
            if (entry.subscription) {
              entry.subscription.unsubscribe()
              if (centrifuge) {
                centrifuge.removeSubscription(entry.subscription)
              }
            }
            subscriptions.delete(normalizedChannel)
          }
        },
      };
    };

    const unsubscribe = (channel) => {
      const normalizedChannel = normalizeChannel(channel)
      const entry = subscriptions.get(normalizedChannel)
      if (!entry) {
        return
      }

      if (entry.subscription) {
        entry.subscription.unsubscribe()
        if (centrifuge) {
          centrifuge.removeSubscription(entry.subscription)
        }
      }
      subscriptions.delete(normalizedChannel)
    };

    const api = { connect, disconnect, subscribe, unsubscribe };

    // Make the API available throughout the app
    app.provide('centrifugo', api);
    app.provide('centrifugoState', state);
    app.config.globalProperties.$centrifugo = api;

    // Set up store subscription
    const mainStore = useMainStore();
    const orderStatusStore = useOrderStatusStore()
    const taskStatusStore = useTaskStatusStore()
    const courierStatusStore = useCourierStatusStore()
    let internalSubscriptions = []

    const bindInternalChannels = () => {
      if (internalSubscriptions.length) {
        return
      }

      internalSubscriptions = [
        subscribe('order_status', (data) => orderStatusStore.updateOrderStatus(data)),
        subscribe('task_status', (data) => taskStatusStore.updateStatus(data)),
        subscribe('courier_status', (data) => courierStatusStore.updateStatus(data)),
      ]
    }

    const clearInternalChannels = () => {
      internalSubscriptions.forEach((sub) => sub?.unsubscribe?.())
      internalSubscriptions = []
    }

    // Initial connection check
    if (mainStore.isAuthenticated) {
      bindInternalChannels();
      connect();
    }

    // Watch for authentication state changes
    mainStore.$subscribe((mutation, state) => {
      if (state.isAuthenticated) {
        bindInternalChannels();
        connect();
      } else {
        clearInternalChannels();
        disconnect();
        subscriptions.clear();
      }
    });
  },
};
