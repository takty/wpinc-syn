/**
 * Post Status Plugin
 *
 * @author Takuto Yanagida
 * @version 2023-06-27
 */

 (() => {
	const {
		data      : { useSelect },
		coreData  : { useEntityProp },
		plugins   : { registerPlugin },
		element   : { createElement: el, Fragment },
		components: { CheckboxControl },
		editPost  : { PluginPostStatusInfo },
	} = wp;

	const meta_keys = wpinc_post_status.meta_keys ?? [];
	const labels    = wpinc_post_status.labels    ?? [];

	const MetaField = ({ meta_key, label }) => {
		const pt              = useSelect(s => s('core/editor').getCurrentPostType(), []);
		const [meta, setMeta] = useEntityProp('postType', pt, 'meta');
		const updateMeta      = (k, v) => setMeta({ ...meta, [k]: (v ? v : null) });

		return el(CheckboxControl, {
			label,
			checked : meta[meta_key],
			onChange: v => updateMeta(meta_key, v),
		});
	};

	const render = () => el(
		Fragment,
		{},
		_.zip(meta_keys, labels).map(
			([meta_key, label]) => el(
				PluginPostStatusInfo,
				{},
				el (
					MetaField,
					{ meta_key, label }
				)
			)
		)
	);

	registerPlugin('wpinc-post-status', { render });
})();
