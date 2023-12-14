FROM mcr.microsoft.com/playwright:v1.39.0-jammy

WORKDIR app

# Copy required files for running tests
COPY tests/ tests/
COPY utils/ utils/
COPY playwright.config.ts .
COPY package-lock.json .
COPY tsconfig.json .

# Install dependencies
RUN npm ci --ignore-scripts

# Set permissions for the /app directory
RUN chmod -R 777 /app
