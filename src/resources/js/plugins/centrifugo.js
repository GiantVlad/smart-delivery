import { Centrifuge } from 'centrifuge';
import { useMainStore } from '@/stores/main'
import { reactive } from 'vue'

export default {
  install(app, { url }) {
    let centrifuge = null;
    let isConnected = false;
    const subscriptions = new Map();
    const state = reactive({
      connected: false,
      lastError: '',
      lastDisconnectedCode: null,
    })

    const connect = async () => {
      if (isConnected) return;

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

        centrifuge.on('connected', () => {
          console.log('Centrifugo connected');
          isConnected = true;
          state.connected = true
          state.lastError = ''
          state.lastDisconnectedCode = null
          // Resubscribe to all channels after reconnection.
          subscriptions.forEach((entry, channel) => {
            if (entry.subscription) {
              entry.subscription.unsubscribe()
            }

            const sub = centrifuge.newSubscription(channel)
            sub.on('publication', (ctx) => {
              entry.callbacks.forEach((callback) => callback(ctx.data))
            })
            sub.subscribe()
            entry.subscription = sub
          });
        });

        centrifuge.on('disconnected', (ctx) => {
          console.log('Centrifugo disconnected', ctx);
          isConnected = false;
          state.connected = false
          state.lastDisconnectedCode = ctx?.code ?? null
        });

        centrifuge.on('error', (err) => {
          console.error('Centrifugo error:', err);
          state.lastError = err?.message || 'Unknown Centrifugo error'
        });

        centrifuge.connect();
      } catch (error) {
        console.error('Failed to connect to Centrifugo:', error);
      }
    };

    const disconnect = () => {
      if (centrifuge) {
        centrifuge.disconnect();
        centrifuge = null;
        isConnected = false;
        state.connected = false
        subscriptions.forEach((entry) => {
          entry.subscription = null
        })
        console.log('Centrifugo disconnected');
      }
    };

    const subscribe = (channel, callback) => {
      if (!subscriptions.has(channel)) {
        subscriptions.set(channel, {
          callbacks: new Set(),
          subscription: null,
        })
      }

      const entry = subscriptions.get(channel)
      entry.callbacks.add(callback)

      if (centrifuge && !entry.subscription) {
        const sub = centrifuge.newSubscription(channel)
        sub.on('publication', (ctx) => {
          entry.callbacks.forEach((handler) => handler(ctx.data))
        })
        sub.subscribe()
        entry.subscription = sub
      }

      return {
        unsubscribe: () => {
          entry.callbacks.delete(callback)

          if (!entry.callbacks.size) {
            entry.subscription?.unsubscribe()
            subscriptions.delete(channel)
          }
        },
      };
    };

    const unsubscribe = (channel) => {
      const entry = subscriptions.get(channel)
      if (!entry) {
        return
      }

      entry.subscription?.unsubscribe()
      subscriptions.delete(channel)
    };

    const api = { connect, disconnect, subscribe, unsubscribe };

    // Make the API available throughout the app
    app.provide('centrifugo', api);
    app.provide('centrifugoState', state);
    app.config.globalProperties.$centrifugo = api;

    // Set up store subscription
    const mainStore = useMainStore();

    // Initial connection check
    if (mainStore.isAuthenticated) {
      connect();
    }

    // Watch for authentication state changes
    const unwatch = mainStore.$subscribe((mutation, state) => {
      if (state.isAuthenticated) {
        connect();
      } else {
        disconnect();
        subscriptions.clear();
      }
    });

    // Clean up on app unmount
    app.mixin({
      unmounted() {
        unwatch();
      },
    });
  },
};
