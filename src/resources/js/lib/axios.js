import axiosLib from "axios";
import { useMainStore } from "@/stores/main";
import { useRouter } from "vue-router";

const http = axiosLib.create({
  baseURL: "https://delivery.cloud-workflow.com",
  timeout: 60000,
  withCredentials: true,
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
  withXSRFToken: true,
  headers: {
    Accept: "application/json"
  }
});

// Add a response interceptor
http.interceptors.response.use(
  response => response,
  error => {
    const router = useRouter();
    const mainStore = useMainStore();

    if (error.response?.status === 401) {
      // Clear user data from the store
      mainStore.clearStore();

      const currentRoute = router.currentRoute.value;
      if (!currentRoute || currentRoute.name !== 'login') {
        router.push({
          name: 'login',
          query: {
            redirect: currentRoute?.fullPath || '/'
          }
        });
      }
    }

    return Promise.reject(error);
  }
);

export default http;
