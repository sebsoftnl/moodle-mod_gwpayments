Version 1.0.1 (build 2021081601)

** Added global default: currency
** Added options to hide/disable payment button when "invalid"
** Added warnings for misconfigurations or when payments cannot be performed
** Some small code fixes
** Fixed nullability in install.xml (including upgrade.php even though not needed except internally)
** Fixed/changed gwpayments_cm_info_dynamic to use INSTANCE config over global config
** Fixed typo to \core_user\fields (instead of non-existing core\user_fields)
** Moved *_get_completion_state to deprecatedlib.php
** Introduced new custom_completion class
** Added mod_gwpayments_get_completion_active_rule_descriptions()

-----

Version 1.0.0 (build 2021081600)

** Initial version

-----
