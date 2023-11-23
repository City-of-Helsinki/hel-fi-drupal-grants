FROM node:20

# Copy application source
COPY . /app
WORKDIR /app

# Install dependencies
RUN npm ci --ignore-scripts

# Install Playwright dependencies
RUN npx playwright install --with-deps

# Set permissions for the /app directory
RUN chmod -R 777 /app
