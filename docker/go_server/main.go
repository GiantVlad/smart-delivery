package main

import (
	"bytes"
        "encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"net/url"
	"time"
)

// Response struct to capture data from the external request
type Response struct {
	Status     string `json:"status"`
	StatusCode int    `json:"status_code"`
	Body       string `json:"body"`
}

type Order struct {
	Uuid string `json:"uuid"`
}

// Handler for receiving and logging incoming requests
func receiveHandler(w http.ResponseWriter, r *http.Request) {
        if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
        }
        
        // Read request body
	body, err := io.ReadAll(r.Body)
	if err != nil {
		http.Error(w, "Could not read request body", http.StatusInternalServerError)
		return
	}
	defer r.Body.Close()

	// Log the request
	fmt.Printf("Received request: %s\n", string(body))

	// Respond with a confirmation message
	w.WriteHeader(http.StatusOK)
	w.Write([]byte("Received your request."))
}

// Handler to send a fake request to a specified URL
func sendHandler(w http.ResponseWriter, r *http.Request) {
	// Extract target URL from query parameters
	targetURL := "http://roadrunner/erp-web-hook"

	// Validate the target URL
	_, err := url.ParseRequestURI(targetURL)
	if err != nil {
		http.Error(w, "Invalid URL format", http.StatusBadRequest)
		return
	}

	// Create an HTTP client with a timeout
	client := &http.Client{Timeout: 10 * time.Second}

        order := Order{
		Uuid: "12345",
	}

	// Convert the Person object to JSON
	jsonData, err := json.Marshal(order)

	// Send the request to the target URL
	resp, err := client.Post(targetURL, "application/json", bytes.NewBuffer(jsonData))
	if err != nil {
		http.Error(w, "Failed to send request", http.StatusInternalServerError)
		return
	}
	defer resp.Body.Close()

	// Read the response body
	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		http.Error(w, "Failed to read response body", http.StatusInternalServerError)
		return
	}

	// Prepare and encode the response to JSON
	response := Response{
		Status:     resp.Status,
		StatusCode: resp.StatusCode,
		Body:       string(respBody),
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}

func main() {
	http.HandleFunc("/erp", receiveHandler)
	http.HandleFunc("/web-hook", sendHandler)

	port := "8090"
	fmt.Printf("Server starting on port %s\n", port)
	log.Fatal(http.ListenAndServe(":"+port, nil))
}

