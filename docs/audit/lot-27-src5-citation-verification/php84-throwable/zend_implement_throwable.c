/* {{{ zend_implement_throwable */
static int zend_implement_throwable(zend_class_entry *interface, zend_class_entry *class_type)
{
	/* zend_ce_exception and zend_ce_error may not be initialized yet when this is called (e.g when
	 * implementing Throwable for Exception itself). Perform a manual inheritance check. */
	zend_class_entry *root = class_type;
	while (root->parent) {
		root = root->parent;
	}
	if (zend_string_equals_literal(root->name, "Exception")
			|| zend_string_equals_literal(root->name, "Error")) {
		return SUCCESS;
	}

	bool can_extend = (class_type->ce_flags & ZEND_ACC_ENUM) == 0;

	zend_error_noreturn(E_ERROR,
		can_extend
			? "%s %s cannot implement interface %s, extend Exception or Error instead"
			: "%s %s cannot implement interface %s",
		zend_get_object_type_uc(class_type),
		ZSTR_VAL(class_type->name),
		ZSTR_VAL(interface->name));
	return FAILURE;
}
