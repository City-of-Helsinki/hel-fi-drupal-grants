test-pw: ## Run E2E tests in a container
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:

test-pw-headed: ## Run E2E tests in a container
	@docker compose exec e2e sh -c "npm install -y --silent && npx playwright test $(filter-out $@,$(MAKECMDGOALS)) --headed"
%:
	@:
