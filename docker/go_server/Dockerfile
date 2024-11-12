# Use the official Golang image as the build environment
FROM golang:1.20 AS builder

# Set the working directory inside the container
WORKDIR /app

# Copy the source code to the container
COPY . .

# Initialize Go modules and tidy up dependencies (in case go.mod is not yet created)
RUN go mod init smart-delivery.dev/go-http-server || true
RUN go mod tidy

# Build the Go application
RUN go build -o server .

# Use a minimal base image for the final stage
FROM debian:bookworm-slim

# Set the working directory in the minimal container
WORKDIR /app

# Copy the compiled Go binary from the builder
COPY --from=builder /app/server .

# Expose the application port
EXPOSE 8090

# Run the application
CMD ["/app/server"]
