/**
 * Block Editor Plugin for Sticky
 *
 * @author Takuto Yanagida
 * @version 2022-02-20
 */

(() => {
	const el = wp.element.createElement;

	const { __ }                     = wp.i18n;
	const { useSelect, useDispatch } = wp.data;
	const { CheckboxControl }        = wp.components;
	const { PluginPostStatusInfo }   = wp.editPost;
	const { registerPlugin }         = wp.plugins;

	const PMK = wpinc_sticky.PMK;

	const MetaBlockField = () => {
		const { postMeta } = useSelect((select) => {
			return {
				postMeta: select('core/editor').getEditedPostAttribute('meta'),
			};
		});
		const { editPost } = useDispatch('core/editor', [postMeta[PMK]]);

		return el(CheckboxControl, {
			label   : __('Stick this post at the top', 'wpinc'),
			checked : postMeta[PMK],
			onChange: (value) => editPost({ meta: { [PMK]: value ? 1 : null } }),
		});
	};

	const render = () => el(
		PluginPostStatusInfo,
		{},
		el(MetaBlockField)
	);

	registerPlugin('wpinc-sticky', { render });
})();
