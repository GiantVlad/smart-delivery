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
	"math/rand"
	"errors"
)

// Response struct to capture data from the external request
type Response struct {
	Status     string `json:"status"`
	StatusCode int    `json:"status_code"`
	Body       string `json:"body"`
}

type Order struct {
	Uuid string `json:"orderUuid"`
	Status string `json:"status"`
}

// Handler for receiving and logging incoming requests
func receiveHandler(w http.ResponseWriter, r *http.Request) {
    if !(r.Method == http.MethodPost || r.Method == http.MethodPut) {
        http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
            return
    }

    body, err := io.ReadAll(r.Body)
    if err != nil {
        http.Error(w, "Could not read request body", http.StatusInternalServerError)
        return
    }
    defer r.Body.Close()

    var o Order
	err = json.Unmarshal(body, &o)
	if err != nil {
		panic(err)
	}

    go func(ord Order) {
        ord.Status = "accepted"
        if !randomBoolWithFalseChance() {
            ord.Status = "declined"
        }

        err := sendWebhook(ord)
        if err != nil {
            log.Println("Failed to send confirmation:", err)
        } else {
            log.Println("Confirmation has been sent successfully")
        }
    }(o)

    fmt.Printf("Received request: %s\n", o.Uuid)

	w.WriteHeader(http.StatusOK)
	w.Write([]byte("Received your request."))
}

func sendWebhook(o Order) error {
    client := &http.Client{Timeout: 10 * time.Second}
    targetURL := "http://roadrunner:8000/api/erp-webhook"
    _, err := url.ParseRequestURI(targetURL)
    if err != nil {
        return err
    }
    jsonData, err := json.Marshal(o)
    // delay
    time.Sleep(120 * time.Second)

    resp, err := client.Post(targetURL, "application/json", bytes.NewBuffer(jsonData))
    if err != nil {
        return err
    }
    defer resp.Body.Close()

    bodyBytes, err = io.Copy(io.Discard, resp.Body)
        if err != nil {
            return err
        }
    if resp.StatusCode == http.StatusOK {
        log.Info(string(bodyBytes))
    } else {
       return errors.New(string(bodyBytes))
    }

    return err
}

func randomBoolWithFalseChance() bool {
	randomValue := rand.Intn(100)

	return randomValue >= 20
}

// Handler to send a fake request to a specified URL
func sendHandler(w http.ResponseWriter, r *http.Request) {
	return
}

func main() {
    rand.Seed(time.Now().UnixNano())
	http.HandleFunc("/erp", receiveHandler)

	port := "8090"
	fmt.Printf("Server starting on port %s\n", port)
	log.Fatal(http.ListenAndServe(":"+port, nil))
}
