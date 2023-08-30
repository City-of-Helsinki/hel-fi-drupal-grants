PHONY += test-clean-data
test-clean-data: ## Clean environment for testing
	$(call robot/clean-env-for-testing.sh)
	
PHONY += test-robot
test-robot: ## Run Robot Framework tests in a Docker container
	docker run --rm \
		-v $(PWD)/robot:/robot \
		-e ROBOT_OPTIONS="${ROBOT_OPTIONS}" \
		--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
		--net="host" \
		-it \
		marketsquare/robotframework-browser:17.3 \
		bash -c "robot --outputdir robot/logs /robot"
