PHONY += test-clean-data
test-clean-data: ## Clean environment for testing
	$(call test/clean-env-for-testing.sh)
	
PHONY += test-robot
test-robot: ## Run Robot Framework tests in a Docker container
	docker run --rm \
		-v $(PWD)/test:/test \
		-e ROBOT_OPTIONS="${ROBOT_OPTIONS}" \
		--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
		--net="host" \
		-it \
		marketsquare/robotframework-browser:17.3 \
		bash -c "robot --outputdir test/logs /test"
