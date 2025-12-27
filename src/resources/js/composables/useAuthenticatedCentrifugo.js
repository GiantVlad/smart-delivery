import { ref, onUnmounted, onMounted } from 'vue';
import axios from 'axios';

export default function useAuthenticatedCentrifugo() {
    const centrifugo = ref(null);
    const isConnected = ref(false);
    const error = ref(null);
    const isConnecting = ref(false);
    const subscriptions = new Map();
    const reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const reconnectDelay = 3000; // 3 seconds

    const connect = async () => {
        if (isConnecting.value || isConnected.value) return;
        
        isConnecting.value = true;
        error.value = null;

        try {
            // Get the connection token from your backend
            const response = await axios.get('/api/centrifugo/connection-token');
            const token = response.data.token;
            
            if (!token) {
                throw new Error('No token received from server');
            }

            // Initialize Centrifugo with the token
            centrifugo.value = new WebSocket(`ws://${window.location.hostname}:8010/connection/websocket`);
            
            centrifugo.value.onopen = () => {
                console.log('Centrifugo connected');
                isConnected.value = true;
                isConnecting.value = false;
                reconnectAttempts = 0;
                
                // Resubscribe to all channels after reconnection
                subscriptions.forEach(({ channel, callback }) => {
                    subscribe(channel, callback);
                });
            };

            centrifugo.value.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    
                    // Handle subscription messages
                    if (data.channel && subscriptions.has(data.channel)) {
                        const { callback } = subscriptions.get(data.channel);
                        if (data.data) {
                            callback(JSON.parse(data.data));
                        }
                    }
                } catch (e) {
                    console.error('Error parsing Centrifugo message:', e);
                }
            };

            centrifugo.value.onclose = () => {
                console.log('Centrifugo disconnected');
                isConnected.value = false;
                isConnecting.value = false;
                
                // Try to reconnect if we haven't exceeded max attempts
                if (reconnectAttempts < maxReconnectAttempts) {
                    console.log(`Attempting to reconnect (${reconnectAttempts + 1}/${maxReconnectAttempts})...`);
                    reconnectAttempts++;
                    setTimeout(connect, reconnectDelay);
                } else {
                    error.value = 'Failed to connect to Centrifugo after multiple attempts';
                }
            };

            centrifugo.value.onerror = (err) => {
                console.error('Centrifugo error:', err);
                error.value = 'Connection error';
                isConnecting.value = false;
            };
            
            // Send connect message with token
            const connectMsg = {
                method: 'connect',
                params: {
                    token: token
                }
            };
            
            centrifugo.value.send(JSON.stringify(connectMsg));
            
        } catch (err) {
            console.error('Failed to connect to Centrifugo:', err);
            error.value = err.message || 'Failed to connect to Centrifugo';
            isConnecting.value = false;
            
            // Retry connection after delay
            if (reconnectAttempts < maxReconnectAttempts) {
                reconnectAttempts++;
                setTimeout(connect, reconnectDelay);
            }
        }
    };

    const disconnect = () => {
        if (centrifugo.value) {
            // Unsubscribe from all channels
            subscriptions.forEach(({ channel }) => {
                unsubscribe(channel);
            });
            
            // Send disconnect message
            const disconnectMsg = {
                method: 'disconnect',
                params: {}
            };
            
            centrifugo.value.send(JSON.stringify(disconnectMsg));
            centrifugo.value.close();
            centrifugo.value = null;
            isConnected.value = false;
        }
    };

    const subscribe = async (channel, callback) => {
        if (!centrifugo.value || !isConnected.value) {
            await connect();
        }

        // Store subscription
        subscriptions.set(channel, { channel, callback });

        // Send subscribe command
        const subscribeMsg = {
            method: 'subscribe',
            params: {
                channel: channel
            }
        };

        centrifugo.value.send(JSON.stringify(subscribeMsg));
    };

    const unsubscribe = (channel) => {
        if (!centrifugo.value || !isConnected.value) return;

        // Remove subscription
        subscriptions.delete(channel);

        // Send unsubscribe command
        const unsubscribeMsg = {
            method: 'unsubscribe',
            params: {
                channel: channel
            }
        };

        centrifugo.value.send(JSON.stringify(unsubscribeMsg));
    };

    // Clean up on component unmount
    onUnmounted(() => {
        disconnect();
    });

    return {
        connect,
        disconnect,
        subscribe,
        unsubscribe,
        isConnected,
        isConnecting,
        error
    };
}
