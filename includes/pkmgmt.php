<?php

Class pkmgmt
{
	const post_type = 'pkmgmt';
	
	function pkmgmt_array_flatten( $input ): array
    {
		if ( ! is_array( $input ) )
			return array( $input );
	
		$output = array();
	
		foreach ( $input as $value )
			$output = array_merge( $output, $this->pkmgmt_array_flatten( $value ) );
	
		return $output;
	}
	
	
	function pkmgmt_normalize_newline( $text, $to = "\n" ) {
		if ( ! is_string( $text ) )
			return $text;
	
		$nls = array( "\r\n", "\r", "\n" );
	
		if ( ! in_array( $to, $nls ) )
			return $text;
	
		return str_replace( $nls, $to, $text );
	}
	
	function pkmgmt_normalize_newline_deep( $arr, $to = "\n" ) {
		if ( is_array( $arr ) ) {
			$result = array();
	
			foreach ( $arr as $key => $text )
				$result[$key] = $this->pkmgmt_normalize_newline_deep( $text, $to );
	
			return $result;
		}
	
		return $this->pkmgmt_normalize_newline( $arr, $to );
	}

	function pkmgmt_current_action() 
	{
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			return $_REQUEST['action'];

		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			return $_REQUEST['action2'];

		return false;
	}
	
	public function getPost(): array
    {
		$return = array();
		foreach ( pkmgmt_site::get_fields() as $element )
			$return[$element] = $_POST[$element] ?? '';
		return $return;
	}

	function pkmgmt_messages()
	{
		$messages = array(
		
			'validation_error' => array(
				'description' => __( "Validation errors occurred", 'parking-management' ),
				'default' => __( 'Validation errors occurred. Please confirm the fields and submit it again.', 'parking-management' )
			),
	
			'invalid_required' => array(
				'description' => __( "There is a field that the sender must fill in", 'parking-management' ),
				'default' => __( 'Please fill the required field.', 'parking-management' )
			)
		);
	
		return apply_filters( 'pkmgmt_messages', $messages );
	}

	function pkmgmt_is_rtl(): bool
    {
		if ( function_exists( 'is_rtl' ) )
			return is_rtl();
	
		return false;
	}
	
	public function get_request_uri(): string
    {
		static $request_uri = '';

		if ( empty( $request_uri ) )
			$request_uri = add_query_arg( array() );
		return esc_url_raw( $request_uri );
	}
	
	function pkmgmt_enctype_value( $enctype ): string
    {
		$enctype = trim( $enctype );
		
	
		if ( empty( $enctype ) )
			return '';
	
		$valid_enc_types = array(
			'application/x-www-form-urlencoded',
			'multipart/form-data',
			'text/plain' );
	
		if ( in_array( $enctype, $valid_enc_types ) ) {
			return $enctype;
		}
	
		$pattern = '%^enctype="(' . implode( '|', $valid_enc_types ) . ')"$%';
	
		if ( preg_match( $pattern, $enctype, $matches ) ) {
			return $matches[1]; // for back-compat
		}
		return '';
	}
	function pkmgmt_plugin_url( $path = '' ): string
    {
		$url = untrailingslashit( PKMGMT_PLUGIN_URL );
	
		if ( ! empty( $path ) && is_string( $path ) && false === strpos( $path, '..' ) )
			$url .= '/' . ltrim( $path, '/' );
		return $url;
	}

}

if ( ! function_exists( "print_log") ) :
function print_log($object, bool $out = true): void
{
	print "<pre>";
	print_r($object);
	print "</pre>";
	if ($out)
		exit(1);
}
endif;
if ( ! function_exists( "debug_to_console") ) :
	function debug_to_console($data): void
	{
		$output = print_r($data, true);
		echo "<script>console.log('Debug Objects: $output );</script>";
	}
endif;

if ( ! function_exists( "throw_log") ) :
    /**
     * @throws Exception
     */
    function throw_log($object)
{
	throw new Exception(print_r($object,true));
}
endif;

