
test-pw-profiles: ## make test-pw-profiles
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project profiles $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

# Define the default project name
PROJECT_NAME := profiles

# Override the project name if provided as an argument
ifdef PROJECT
	PROJECT_NAME := $(PROJECT)
endif

test-pw-p: ## Example: make test-pw-ph PROJECT=forms-29
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project $(PROJECT_NAME) $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

test-pw-ph: ## Run E2E tests in a container. Example: make test-pw-ph PROJECT=forms-29
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project $(PROJECT_NAME) $(filter-out $@,$(MAKECMDGOALS)) --headed"
%:
	@:

test-pw: ## Run E2E tests in a container normally
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

test-pw-headed: ## Run E2E tests in a container with headed
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test $(filter-out $@,$(MAKECMDGOALS)) --headed"
%:
	@:
