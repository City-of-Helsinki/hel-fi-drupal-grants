
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

delete-docs-all: ## Example: make
	bash ./e2e/clean-env.sh

delete-docs-draft: ## Example: make
	bash ./e2e/clean-env.sh --draft

test-pw-p: ## Example: make test-pw-p PROJECT=forms-29
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project $(PROJECT_NAME) $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

test-pw-ph: ## Run E2E HEADED tests in a container. Example: make test-pw-ph PROJECT=forms-29
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project $(PROJECT_NAME) $(filter-out $@,$(MAKECMDGOALS)) --headed"
%:
	@:

test-pw: ## Run E2E tests in a container normally
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project=forms-all"
%:
	@:

test-pw-headed: ## Run E2E tests in a container with headed
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project=forms-all --headed"
%:
	@:
