FROM registry.access.redhat.com/ubi8/nodejs-20

ENV PLAYWRIGHT_BROWSERS_PATH=0

# Create and set the working directory with correct permissions
RUN mkdir -p /app && chmod -R 777 /app
WORKDIR app

# Copy required files for running tests
COPY tests/ tests/
COPY utils/ utils/
COPY playwright.config.ts .
COPY package.json .
COPY package-lock.json .
COPY tsconfig.json .

# Install dependencies
RUN npm ci --ignore-scripts

# Install Playwright dependencies
RUN npx playwright install --with-deps

# Set permissions for the /app directory
RUN chmod -R 777 /app
