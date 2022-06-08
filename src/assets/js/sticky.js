/**
 * Sticky Plugin
 *
 * @author Takuto Yanagida
 * @version 2022-06-08
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

	const meta_keys = wpinc_sticky.meta_keys ?? ['_sticky'];
	const labels    = wpinc_sticky.labels    ?? ['Stick this post at the top'];

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

	registerPlugin('wpinc-sticky', { render });
})();
