FROM node:20

# Copy application source
COPY . /app
WORKDIR /app

# Install dependencies
RUN npm ci

# Install Playwright dependencies
RUN npx playwright install --with-deps

# The command to run when the container starts
CMD ["npm", "start"]
