/*
 * Gutenberg block Javascript code
 */
    var __                = wp.i18n.__, // The __() function for internationalization.
     el     = wp.element.createElement, // The wp.element.el() function to create elements.
     registerBlockType = wp.blocks.registerBlockType, // The registerBlockType() function to register blocks.
	 InspectorControls = wp.editor.InspectorControls,
	 ServerSideRender = wp.components.ServerSideRender,
	 Button = wp.components.Button,
	 Dashicon = wp.components.Dashicon,
	 IconButton = wp.components.IconButton,
	 RichText = wp.editor.RichText,
	 Editable = wp.blocks.Editable, // Editable component of React.
	 MediaUpload = wp.editor.MediaUpload,
	 MediaUploadCheck = wp.editor.MediaUploadCheck,
	 TextControl = wp.components.TextControl,
	 SelectControl = wp.components.SelectControl,
	 RadioControl = wp.components.RadioControl,
	 Toolbar = wp.components.Toolbar

	 // console.log(r3dfb_ids)

	 var r3dfb = jQuery.parseJSON(r3dfb);
	 // console.log(r3dfb)

	 var r3dfb_selectFlipbok = [{label:'', value:''}]

	 for (var i = 0; i < r3dfb.length; i++){
	 	var b = r3dfb[i]
	 	r3dfb_selectFlipbok.push({label:b.name, value:b.id})
	 }
	

	/**
     * Register block
     *
     * @param  {string}   name     Block name.
     * @param  {Object}   settings Block settings.
     * @return {?WPBlock}          Block itself, if registered successfully,
     *                             otherwise "undefined".
     */
    registerBlockType(
		'r3dfb/embed', // Block name. Must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.	
        {
            title: 'Real3D FlipBook', // Block title. __() function allows for internationalization.
            description: 'Display PDF or images as flipbook',

            icon:{
			    // Specifying a background color to appear with the icon e.g.: in the inserter.
			    // background: '#999',
			    // Specifying a color for the icon (optional: if not set, a readable color will be automatically defined)
			    // foreground: '#000',
			    // Specifying a dashicon for the block
			    src: 'book',
			},


            // icon: 'book', // Block icon from Dashicons. https://developer.wordpress.org/resource/dashicons/.
			category: 'common', // Block category. Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
            attributes: {
				id: {
                    type: 'string'
                },
                pdf: {
                    type: 'string'
                },
                mode:{
                    type: 'string',
                    default:'normal'
                },
                pages:{
                    type: 'string',
                    default:''
                },
            },


            // Defines the block within the editor.
            edit: function( props ) {
				
				var {attributes , setAttributes, focus, className} = props;
                			
				function onSelectPDF(media) {
                    return props.setAttributes({
                        pdf: media.url
                    });
                }

                function onSelectImages(media){
                	// console.log(media)
                	var arr = []
                	for (var i = 0; i < media.length; i++) {
                		arr.push(media[i].url)
          			}
          			var p = arr.join(";")
          			debugger
                	return props.setAttributes({
                        pages: p
                    });

                }

                function onChangeWidth(v) {
                    setAttributes( {width: v} );
                }

                function onChangeHeight(v) {
                    setAttributes( {height: v} );
                }
				
				function onChangeMode(v) {
                    setAttributes( {mode: v} );
                }

                function onChangeId(v) {
                    setAttributes( {id: v} );
                }

                function onChangeToolbarfixed(v) {
                    setAttributes( {toolbarfixed: v} );
                }

                var attributes = props.attributes || "";
                var pdf = attributes.pdf || ''
				
				return [

					el(
						'div',
						null,
						'Real3D Flipbook'
					),

					// el(
					// 	'div',
					// 	null,
					// 	'Create flipbook from PDF'
					// ),

					// el(
					// 	'div',
					// 	// {className: "wp-block-shortcode"},
					// 	{},
					// 	el( MediaUploadCheck, 
	    //         	     	null, 
	    //         	     	el( 'div', 
	    //         	     		null, 
	    //         	     		el( MediaUpload, 
	    //         	     			{
					// 		            onSelect: onSelectPDF,
					// 		            allowedTypes: ['application/pdf'],
					// 		            // value: "val",
					// 		            render: function render(_ref5) {
					// 		              var open = _ref5.open;
					// 		              return el(IconButton, {
					// 		                // className: "components-toolbar__control",
					// 		                label: 'Upload PDF',
					// 		                icon: "media-document",
					// 		                onClick: open
					// 		              }, "Upload PDF");
					// 		            }
					// 		          }
					// 	          ),
	    //         	     		el( TextControl,
					// 				{
					// 					// label: 'PDF url',
					// 					value: attributes.pdf,
					// 					onChange: onChangeWidth,
					// 					placeholder:"Flipbook source PDF url"
					// 				}
					// 			)
					// 		),

					// 		el(
					// 			'div',
					// 			null,
					// 			'Create flipbook from images'
					// 		),

	    //         	     	el( 'div', 
	    //         	     		null, 
					// 			el( MediaUpload, 
	    //         	     			{
					// 		            onSelect: onSelectImages,
					// 		            allowedTypes: ['image'],
					// 		            multiple:true,
					// 		            // value: "val",
					// 		            render: function render(_ref5) {
					// 		              var open = _ref5.open;
					// 		              return el(IconButton, {
					// 		                // className: "components-toolbar__control",
					// 		                label: 'Upload images',
					// 		                icon: "media-document",
					// 		                onClick: open
					// 		              }, "Upload images");
					// 		            }
					// 		          }
					// 	          )

	    //         	     		)
					// 		),

					// 		el(
					// 			'div',
					// 			null,
					// 			'Embed existing flipook'
					// 		),

							el( SelectControl,{
								label: 'Select flipbook',
								value: attributes.id,
								options: r3dfb_selectFlipbok,
								onChange: onChangeId
							}),
					// ),
										
					el( 
						InspectorControls, 
						{ key: 'inspector' }, // Display the block options in the inspector pancreateElement.
						el(
							'div',
							{ className: 'r3dfb_div_main'}	,
							el(
								'hr',
								{},
							),
						
							// el(
							// 	TextControl,
							// 	{
							// 		label: 'PDF url',
							// 		value: attributes.pdf,
							// 		onChange: onChangeWidth
							// 	}
							// ),

							el(
								SelectControl,
									{
										label: 'Select Flipbook',
										value: attributes.id,
										options: r3dfb_selectFlipbok,
										onChange: onChangeId
									}
							),

							el(
								SelectControl,
									{
										label: 'Mode',
										value: attributes.mode,
										options: [
											{ label: 'Normal', value: 'normal' },
											{ label: 'Lightbox', value: 'lightbox' },
											{ label: 'Fullscreen', value: 'fullscreen' }
										],
										onChange: onChangeMode
									}
							),


						),
					),
                ];
            },

            // Defines the saved block.

			save: function save(props) {

				var attributes = props.attributes;
				var id = attributes.id
				var pdf = attributes.pdf
				var mode = attributes.mode
				var pages = attributes.pages

				var shortcodeString = '[real3dflipbook'
				if(id) shortcodeString += (' id="' + id + '"');
				if(pdf) shortcodeString += (' pdf="' + pdf + '"');
				if(pages) shortcodeString += (' pages="' + pages + '"');
				if(mode) shortcodeString += (' mode ="' + mode + '"');
				shortcodeString += ']'

				return shortcodeString

			}


        }
    );
