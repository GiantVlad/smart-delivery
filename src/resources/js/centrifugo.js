import {Centrifuge} from 'centrifuge';

const centrifuge = new Centrifuge("ws://centrifuge:8010/connection/websocket");

// Set up token-based authentication if needed
centrifuge.setToken("your-jwt-token");

centrifuge.on("connect", (context) => {
  console.log("Connected to Centrifugo:", context);
});

centrifuge.on("disconnect", (context) => {
  console.log("Disconnected from Centrifugo:", context);
});

export default centrifuge;
