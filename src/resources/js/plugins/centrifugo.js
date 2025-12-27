import { Centrifuge } from 'centrifuge';
import { useMainStore } from '@/stores/main'

export default {
  install(app, { url }) {
    let centrifuge = null;
    let isConnected = false;
    const subscriptions = new Map();

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
          // Resubscribe to all channels after reconnection
          subscriptions.forEach(({ channel, callback }) => {
            const sub = centrifuge.newSubscription(channel);
            sub.on('publication', (ctx) => callback(ctx.data));
            sub.subscribe();
          });
        });

        centrifuge.on('disconnected', (ctx) => {
          console.log('Centrifugo disconnected', ctx);
          isConnected = false;
        });

        centrifuge.on('error', (err) => {
          console.error('Centrifugo error:', err);
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
        console.log('Centrifugo disconnected');
      }
    };

    const subscribe = (channel, callback) => {
      if (!centrifuge) {
        console.warn('Centrifuge not initialized. Call connect() first.');
        return null;
      }

      // Store the subscription for reconnection
      subscriptions.set(channel, { channel, callback });

      const sub = centrifuge.newSubscription(channel);
      sub.on('publication', (ctx) => callback(ctx.data));
      sub.subscribe();

      return sub;
    };

    const unsubscribe = (channel) => {
      subscriptions.delete(channel);
      // Note: The actual unsubscription is handled by the subscription object itself
    };

    const api = { connect, disconnect, subscribe, unsubscribe };

    // Make the API available throughout the app
    app.provide('centrifugo', api);
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
