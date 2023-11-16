FROM node:20

# Copy application source
COPY . /app
WORKDIR /app

# Install dependencies
RUN npm ci

# Install Playwright dependencies
RUN PLAYWRIGHT_BROWSERS_PATH=0 npx playwright install --with-deps

# Set permissions for the /app directory
RUN chmod -R 777 /app

# The command to run when the container starts
CMD ["npm", "start"]
