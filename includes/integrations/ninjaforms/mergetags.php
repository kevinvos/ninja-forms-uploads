<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NF_FU_Integrations_NinjaForms_MergeTags {

	/**
	 * NF_FU_Integrations_NinjaForms_MergeTags constructor.
	 */
	public function __construct() {
		add_filter( 'ninja_forms_merge_tag_value_' . NF_FU_File_Uploads::TYPE, array( $this, 'merge_tag_value' ), 10, 2 );
		add_filter( 'ninja_forms_submission_actions', array( $this, 'add_raw_mergetag' ) , 10, 2 );
	}

	/**
	 * Format the file URLs to links using the filename as link text
	 *
	 * @param string $value
	 * @param array $field
	 *
	 * @return string
	 */
	public function merge_tag_value( $value, $field ) {
		if ( is_null( $value ) ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			$value = explode( ',', $value );
		}

		$return = array();
		foreach ( $value as $url ) {
			if ( ! isset( $field['files'] ) ) {
				continue;
			}

			foreach ( $field['files'] as $file ) {
				if ( trim( $url ) !== $file['data']['file_url'] ) {
					continue;
				}

				$upload = NF_File_Uploads()->controllers->uploads->get( $file['data']['upload_id'] );

				if ( false === $upload ) {
					continue;
				}

				$file_url = NF_File_Uploads()->controllers->uploads->get_file_url( $upload->file_url, $upload->data );


				$return[] = sprintf( '<a href="%s" target="_blank">%s</a>', $file_url, $upload->file_name );
			}
		}

		if ( empty( $return ) ) {
			return is_array( $value ) ? $value[0] : $value;
		}

		return implode( '<br>', $return );
	}

	/**
	 * Add raw mergetag which is a comma separated list of URLs. Eg. {field:xxx:raw}
	 *
	 * @param array $actions
	 * @param array $data
	 *
	 * @return array
	 */
	public function add_raw_mergetag( $actions, $data ) {
		$all_merge_tags = Ninja_Forms()->merge_tags;

		foreach ( $data['fields'] as $field ) {
			if ( NF_FU_File_Uploads::TYPE !== $field['settings']['type'] ) {
				continue;
			}

			$existing_values = $all_merge_tags['fields']->get_merge_tags();
			$existing_value  = $existing_values[ 'field_' . $field['settings']['key'] ]['field_value'];

			$dom = new DomDocument();
			$dom->loadHTML( $existing_value );
			$output = array();
			foreach ( $dom->getElementsByTagName( 'a' ) as $item ) {
				$output[] = $item->getAttribute( 'href' );
			}
			$value = implode( ',', $output );
			$all_merge_tags['fields']->add( 'field_' . $field['settings']['key'] . '_raw', $field['settings']['key'], "{field:{$field['settings']['key']}:raw}", $value );
		}

		// Save merge tags
		Ninja_Forms()->merge_tags = $all_merge_tags;

		return $actions;
	}
}
