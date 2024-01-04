FROM mcr.microsoft.com/playwright:v1.40.1-jammy

WORKDIR app/e2e

# Copy required files for running tests
COPY resources/ resources/
COPY tests/ tests/
COPY utils/ utils/
COPY playwright.config.ts .
COPY package-lock.json .
COPY tsconfig.json .

# Install dependencies
RUN npm ci --ignore-scripts

# Set permissions for the /app directory
RUN chmod -R 777 /app
