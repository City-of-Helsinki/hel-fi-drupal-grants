DRUPAL_POST_INSTALL_TARGETS += drush-gwi
AVU_DRUPAL_FRESH_TARGETS := up build sync post-install

PHONY += drush-gwi
drush-gwi: ## Export configuration
	$(call step,Import forms...\n)
	$(call drush,gwi -y)

PHONY += drush-rebuild
drush-rebuild: ## Export configuration
	$(call step,Composer install...\n)
	$(call composer,install)
	$(call step,Run deploy...\n)
	$(call drush,deploy -y)
	$(call step,Import forms...\n)
	$(call drush,gwi -y)
