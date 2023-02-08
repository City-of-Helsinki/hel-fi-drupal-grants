PHONY += test-robot
test-robot: ## Run Robot framework tests in docker container
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
			-e TEST_BASEURL=https://$(DRUPAL_HOSTNAME)/ \
      -e ROBOT_OPTIONS="${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0
