FROM mcr.microsoft.com/playwright:v1.39.0-jammy

WORKDIR app/e2e

COPY package-lock.json .

# Install dependencies
RUN npm ci --ignore-scripts

# Set permissions for the /app directory
RUN chmod -R 777 /app
