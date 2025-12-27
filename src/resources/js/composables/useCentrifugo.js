import { inject, onUnmounted } from 'vue';

export default function useCentrifugo() {
    // Get the centrifugo instance from the plugin
    const centrifugo = inject('centrifugo');
    const state = inject('centrifugoState');
    
    if (!centrifugo) {
        console.error('Centrifugo plugin not found. Make sure it is properly installed.');
        return {
            connect: () => console.error('Centrifugo not available'),
            disconnect: () => {},
            subscribe: () => console.error('Centrifugo not available'),
            unsubscribe: () => {},
            isConnected: false,
            isConnecting: false,
            error: null
        };
    }

    // Store subscriptions for cleanup
    const subscriptions = new Map();
    
    // Subscribe to a channel with automatic cleanup
    const subscribe = (channel, callback) => {
        // Store the subscription for cleanup
        subscriptions.set(channel, { channel, callback });
        
        // Call the actual subscribe method
        centrifugo.subscribe(channel, callback);
        
        // Return a function to unsubscribe
        return () => {
            unsubscribe(channel);
        };
    };
    
    // Unsubscribe from a channel
    const unsubscribe = (channel) => {
        if (subscriptions.has(channel)) {
            centrifugo.unsubscribe(channel);
            subscriptions.delete(channel);
        }
    };
    
    // Clean up all subscriptions when the component is unmounted
    onUnmounted(() => {
        subscriptions.forEach(({ channel }) => {
            centrifugo.unsubscribe(channel);
        });
        subscriptions.clear();
    });
    
    return {
        // Proxy all methods and properties
        connect: centrifugo.connect,
        disconnect: centrifugo.disconnect,
        subscribe,
        unsubscribe,
        get isConnected() { return state.isConnected; },
        get isConnecting() { return state.isConnecting; },
        get error() { return state.error; },
        // For debugging
        _state: state
    };
}
