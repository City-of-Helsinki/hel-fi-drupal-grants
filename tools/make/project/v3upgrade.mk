PHONY += drush-major-update-config
drush-major-update-config: ## Update translations.
	$(call drush,helfi:platform-config:update-config)

PHONY += drush-major-update-db
drush-major-update-db: ## Update translations.
	$(call drush,helfi:platform-config:update-database)
