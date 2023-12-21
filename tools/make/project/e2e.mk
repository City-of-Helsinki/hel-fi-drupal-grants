#test-pw-skiprofile: ## Run E2E tests in a container
#	@docker compose exec e2e sh -c "npm install -y --silent && CREATE_PROFILE=false npx playwright test $(filter-out $@,$(MAKECMDGOALS))"
#%:
#	@:
#

test-pw-profiles: ## Run E2E tests in a container
	@docker compose exec e2e sh -c "npm install -y --silent && CREATE_PROFILE=false npx playwright test --project profiles $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

test-pw-private: ## Run E2E tests in a container
	@docker compose exec e2e sh -c "npm install -y --silent && CREATE_PROFILE=false npx playwright test --project verify-private $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

test-pw: ## Run E2E tests in a container
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

test-pw-headed: ## Run E2E tests in a container
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test $(filter-out $@,$(MAKECMDGOALS)) --headed"
%:
	@:
