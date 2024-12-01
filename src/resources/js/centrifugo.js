import {Centrifuge, UnauthorizedError} from "centrifuge";

export function createCentrifuge (options){
    const centrifuge = new Centrifuge(
      options.url,
      {
        token: options.token,
        // getToken: getToken
      }
    );

    if (options.token) {
      console.log("Token has been set: " + options.token?.substring(0, 5));
      centrifuge.setToken(options.token)
    }

    centrifuge.on("connect", (context) => {
      console.log("Connected to Centrifugo:", context);
    });

    centrifuge.on("disconnect", (context) => {
      console.log("Disconnected from Centrifugo:", context)
    });

    centrifuge.connect();

    return centrifuge
}

async function getToken() {
  // if (!loggedIn) {
  //   return "";
  // }
  const res = await fetch('https://delivery.cloud-workflow.com/centrifuge/connection_token');
  if (!res.ok) {
    if (res.status === 403) {
      // Return special error to not proceed with token refreshes, client will be disconnected.
      throw new UnauthorizedError('Unauthorized');
    }
    // Any other error thrown will result into token refresh re-attempts.
    throw new Error(`Unexpected status code ${res.status}`);
  }
  const data = await res.json();
  return data.token;
}
