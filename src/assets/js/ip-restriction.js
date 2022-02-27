/**
 * IP Restriction Plugin
 *
 * @author Takuto Yanagida
 * @version 2022-02-27
 */

(() => {
	const {
		data      : { useSelect },
		coreData  : { useEntityProp },
		plugins   : { registerPlugin },
		element   : { createElement: el },
		components: { CheckboxControl },
		editPost  : { PluginPostStatusInfo },
	} = wp;

	const meta_keys = wpinc_ip_restriction.meta_keys ?? ['_ip_restriction'];
	const labels    = wpinc_ip_restriction.labels    ?? ['IP Restriction'];

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
		PluginPostStatusInfo,
		{},
		_.zip(meta_keys, labels).map(([meta_key, label]) => el(MetaField, { meta_key, label }))
	);

	registerPlugin('wpinc-ip-restriction', { render });
})();
