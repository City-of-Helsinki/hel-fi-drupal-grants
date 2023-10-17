PHONY += copy-commit-message-script
copy-commit-message-script:
	@$(foreach name,$(shell find public/modules/custom public/themes/custom public/modules/contrib public/themes/contrib -type d -name ".git" -exec dirname {} \; 2> /dev/null ) .,cp tools/commit-msg $(name)/.git/hooks;)

PHONY += copy-post-commit-script
copy-post-commit-script:
	@$(foreach name,$(shell find public/modules/custom public/themes/custom public/modules/contrib public/themes/contrib -type d -name ".git" -exec dirname {} \; 2> /dev/null ) .,cp tools/post-commit $(name)/.git/hooks;)
