import { Centrifuge } from 'centrifuge';
import { useMainStore } from '@/stores/main'

export default {
  install(app, { url }) {
    let centrifuge = null;

    const connect = async () => {
      const res = await fetch('/api/centrifugo/connection-token', {
        credentials: 'same-origin',
      });

      const { token } = await res.json();

      centrifuge = new Centrifuge(url, {
        token,
        getToken: async () => {
          const r = await fetch('/api/centrifugo/connection-token');
          return (await r.json()).token;
        },
      });

        centrifuge.on('connected', ctx => {
          console.log('Centrifugo connected', ctx);
        });

        centrifuge.on('disconnected', ctx => {
          console.error('Centrifugo disconnected', ctx);
        });

        centrifuge.on('error', err => {
          console.error('Centrifugo error', err);
        });
        centrifuge.connect();

    };

    const subscribe = (channel, callback) => {
      if (!centrifuge) return;

      const sub = centrifuge.newSubscription(channel);

      sub.on('publication', ctx => callback(ctx.data));
      sub.subscribe();

      return sub;
    };

    const disconnect = () => centrifuge?.disconnect();

    const api = { connect, subscribe, disconnect };

    app.provide('centrifugo', api);
    app.config.globalProperties.$centrifugo = api;

    const mainStore = useMainStore();

    if (mainStore?.isAuthenticated) {
      connect();
    } else {
      console.log('not authenticated, can not connect to centrifugo')
    }
  }
};
