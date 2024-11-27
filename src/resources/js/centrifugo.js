import { Centrifuge } from "centrifuge";

export default {
  install(app, options) {
    const centrifuge = new Centrifuge(options.url);

    if (options.token) {
      console.log("Token has been set: " + options.token?.substring(0, 5));
      centrifuge.setToken(options.token);
    }

    centrifuge.on("connect", (context) => {
      console.log("Connected to Centrifugo:", context);
    });

    centrifuge.on("disconnect", (context) => {
      console.log("Disconnected from Centrifugo:", context);
    });

    app.config.globalProperties.$centrifuge = centrifuge;

    app.provide("centrifuge", centrifuge);

    centrifuge.connect();
  },
}
