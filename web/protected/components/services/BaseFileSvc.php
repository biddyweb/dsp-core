<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * BaseFileSvc
 * Base File Storage Service giving REST access to file storage.
 */
abstract class BaseFileSvc extends RestService implements iFileManager
{
	/**
	 * @param        $dest_path
	 * @param        $dest_name
	 * @param        $source_file
	 * @param string $contentType
	 * @param bool   $extract
	 * @param bool   $clean
	 * @param bool   $check_exist
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function handleFile( $dest_path, $dest_name, $source_file, $contentType = '', $extract = false, $clean = false, $check_exist = false )
	{
		$ext = FileUtilities::getFileExtension( $source_file );
		if ( empty( $contentType ) )
		{
			$contentType = FileUtilities::determineContentType( $ext, '', $source_file );
		}
		if ( ( FileUtilities::isZipContent( $contentType ) || ( 'zip' === $ext ) ) && $extract )
		{
			// need to extract zip file and move contents to storage
			$zip = new ZipArchive();
			if ( true === $zip->open( $source_file ) )
			{
				return $this->extractZipFile( $dest_path, $zip, $clean );
			}
			else
			{
				throw new Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$name = ( empty( $dest_name ) ? basename( $source_file ) : $dest_name );
			$fullPathName = $dest_path . $name;
			$this->moveFile( $fullPathName, $source_file, $check_exist );

			return array( 'file' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
		}
	}

	/**
	 * @param        $dest_path
	 * @param        $dest_name
	 * @param        $content
	 * @param string $contentType
	 * @param bool   $extract
	 * @param bool   $clean
	 * @param bool   $check_exist
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function handleFileContent( $dest_path, $dest_name, $content, $contentType = '', $extract = false, $clean = false, $check_exist = false )
	{
		$ext = FileUtilities::getFileExtension( $dest_name );
		if ( empty( $contentType ) )
		{
			$contentType = FileUtilities::determineContentType( $ext, $content );
		}
		if ( ( FileUtilities::isZipContent( $contentType ) || ( 'zip' === $ext ) ) && $extract )
		{
			// need to extract zip file and move contents to storage
			$tempDir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
			$tmpName = $tempDir . $dest_name;
			file_put_contents( $tmpName, $content );
			$zip = new ZipArchive();
			if ( true === $zip->open( $tmpName ) )
			{
				return $this->extractZipFile( $dest_path, $zip, $clean );
			}
			else
			{
				throw new Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$fullPathName = $dest_path . $dest_name;
			$this->writeFile( $fullPathName, $content, false, $check_exist );

			return array( 'file' => array( array( 'name' => $dest_name, 'path' => $fullPathName ) ) );
		}
	}

	/**
	 * Swagger output for common api parameters
	 *
	 * @param        $parameters
	 * @param string $method
	 *
	 * @return array
	 */
	public static function swaggerParameters( $parameters, $method = '' )
	{
		$swagger = array();
		foreach ( $parameters as $param )
		{
			switch ( $param )
			{
				case 'order':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "SQL-like order containing field and direction for filter results.",
						"dataType"      => "String",
						"required"      => false,
						"allowMultiple" => true
					);
					break;
				case 'limit':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Set to limit the filter results.",
						"dataType"      => "int",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'include_count':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Include the total number of filter results.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'folder':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the folder to operate on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'file':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the file to operate on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'properties':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Return properties of the folder or file.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'content':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Return the content as base64 of the file, only applies when 'properties' is true.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'download':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Prompt the user to download the file from the browser.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'folders_only':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Only include folders in the folder listing.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'files_only':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Only include files in the folder listing.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'full_tree':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "List the contents of sub-folders as well.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'zip':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Return the zipped content of the folder.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'url':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "URL path of the file to upload.",
						"dataType"      => "string",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'extract':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Extract an uploaded zip file into the folder.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'clean':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Option when 'extract' is true, clean the current folder before extracting files and folders.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'check_exist':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Check if the file or folder exists before attempting to create or update.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
			}
		}

		return $swagger;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerApis()
	{
		$apis = array(
			array(
				'path'        => '/' . $this->_apiName,
				'description' => $this->_description,
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "List root folders and files",
						"notes"          => "Use the available parameters to limit information returned.",
						"responseClass"  => "array",
						"nickname"       => "getRoot",
						"parameters"     => static::swaggerParameters(
							array(
								 'folders_only',
								 'files_only',
								 'full_tree',
								 'zip'
							)
						),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $this->_apiName . '/{folder}/',
				'description' => 'Operations for folders.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "List folders and files in the given folder.",
						"notes"          => "Use the 'folders_only' or 'files_only' parameters to limit returned listing.",
						"responseClass"  => "array",
						"nickname"       => "getFoldersAndFiles",
						"parameters"     => static::swaggerParameters(
							array(
								 'folder',
								 'folders_only',
								 'files_only',
								 'full_tree',
								 'zip'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "POST",
						"summary"        => "Create one or more folders and/or files from posted data.",
						"notes"          => "Post data as an array of folders and/or files.",
						"responseClass"  => "array",
						"nickname"       => "createFoldersAndFiles",
						"parameters"     => static::swaggerParameters(
							array(
								 'folder',
								 'url',
								 'extract',
								 'clean',
								 'check_exist'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one or more folders and/or files",
						"notes"          => "Post data as an array of folders and/or files.",
						"responseClass"  => "array",
						"nickname"       => "updateFoldersAndFiles",
						"parameters"     => static::swaggerParameters(
							array(
								 'folder',
								 'url',
								 'extract',
								 'clean',
								 'check_exist'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete one or more folders and/or files",
						"notes"          => "Use the 'ids' or 'filter' parameter to limit resources that are deleted.",
						"responseClass"  => "array",
						"nickname"       => "deleteFoldersAndFiles",
						"parameters"     => static::swaggerParameters( array( 'folder' ) ),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $this->_apiName . '/{folder}/{file}',
				'description' => 'Operations for a single file.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Download the given file or properties about the file.",
						"notes"          => "Use the 'properties' parameter (optionally add 'content' for base64 content) to list properties of the file.",
						"responseClass"  => "array",
						"nickname"       => "getFile",
						"parameters"     => static::swaggerParameters(
							array(
								 'folder',
								 'file',
								 'properties',
								 'content',
								 'download'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update content of the given file",
						"notes"          => "Post data should be an array of fields for a single record",
						"responseClass"  => "array",
						"nickname"       => "updateFile",
						"parameters"     => static::swaggerParameters( array( 'folder', 'file' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete the given file",
						"notes"          => "DELETE the given FILE FROM the STORAGE.",
						"responseClass"  => "array",
						"nickname"       => "deleteFile",
						"parameters"     => static::swaggerParameters( array( 'folder', 'file' ) ),
						"errorResponses" => array()
					),
				)
			),
		);
//		$apis = array_merge( parent::getSwaggerApis(), $apis );

		return $apis;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionGet()
	{
		$this->checkPermission( 'read' );
		$result = array();
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// list from root
			// or if ending in '/' then resource is a folder
			try
			{
				if ( isset( $_REQUEST['properties'] ) )
				{
					// just properties of the directory itself
					$result = $this->getFolderProperties( $path );
				}
				else
				{
					$asZip = Utilities::boolval( Utilities::getArrayValue( 'zip', $_REQUEST, false ) );
					if ( $asZip )
					{
						$zipFileName = $this->getFolderAsZip( $path );
						$fd = fopen( $zipFileName, "r" );
						if ( $fd )
						{
							$fsize = filesize( $zipFileName );
							$path_parts = pathinfo( $zipFileName );
							header( "Content-type: application/zip" );
							header( "Content-Disposition: filename=\"" . $path_parts["basename"] . "\"" );
							header( "Content-length: $fsize" );
							header( "Cache-control: private" ); //use this to open files directly
							while ( !feof( $fd ) )
							{
								$buffer = fread( $fd, 2048 );
								echo $buffer;
							}
						}
						fclose( $fd );
						unlink( $zipFileName );
						Yii::app()->end();
					}
					else
					{
						$full_tree = ( isset( $_REQUEST['full_tree'] ) ) ? true : false;
						$include_files = true;
						$include_folders = true;
						if ( isset( $_REQUEST['files_only'] ) )
						{
							$include_folders = false;
						}
						elseif ( isset( $_REQUEST['folders_only'] ) )
						{
							$include_files = false;
						}
						$result = $this->getFolderContent( $path, $include_files, $include_folders, $full_tree );
					}
				}
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to retrieve folder content for '$path'.\n{$ex->getMessage()}", $ex->getCode() );
			}
		}
		else
		{
			// resource is a file
			try
			{
				if ( isset( $_REQUEST['properties'] ) )
				{
					// just properties of the file itself
					$content = Utilities::boolval( Utilities::getArrayValue( 'content', $_REQUEST, false ) );
					$result = $this->getFileProperties( $path, $content );
				}
				else
				{
					$download = Utilities::boolval( Utilities::getArrayValue( 'download', $_REQUEST, false ) );
					if ( $download )
					{
						// stream the file, exits processing
						$this->downloadFile( $path );
					}
					else
					{
						// stream the file, exits processing
						$this->streamFile( $path );
					}
				}
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to retrieve file '$path''.\n{$ex->getMessage()}" );
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->checkPermission( 'create' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		$result = array();
		// possible file handling parameters
		$extract = Utilities::boolval( Utilities::getArrayValue( 'extract', $_REQUEST, false ) );
		$clean = Utilities::boolval( Utilities::getArrayValue( 'clean', $_REQUEST, false ) );
		$checkExist = Utilities::boolval( Utilities::getArrayValue( 'check_exist', $_REQUEST, true ) );
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// if ending in '/' then create files or folders in the directory
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				// html5 single posting for file create
				$name = $_SERVER['HTTP_X_FILE_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in create file $fullPathName." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create file $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				// html5 single posting for folder create
				$name = $_SERVER['HTTP_X_FOLDER_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->createFolder( $fullPathName, true, $content, true );
					$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create folder $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multi-part/form-data post, single or multiple files
				$files = $_FILES['files'];
				if ( !is_array( $files['error'] ) )
				{
					// single file
					$name = $files['name'];
					$fullPathName = $path . $name;
					$error = $files['error'];
					if ( $error == UPLOAD_ERR_OK )
					{
						$tmpName = $files['tmp_name'];
						$contentType = $files['type'];
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					else
					{
						$result = array(
							'code'    => 500,
							'message' => "Failed to create file $fullPathName.\n$error"
						);
					}
				}
				else
				{
					$out = array();
					//$files = Utilities::reorgFilePostArray($files);
					foreach ( $files['error'] as $key => $error )
					{
						$name = $files['name'][$key];
						$fullPathName = $path . $name;
						if ( $error == UPLOAD_ERR_OK )
						{
							$tmpName = $files['tmp_name'][$key];
							$contentType = $files['type'][$key];
							$tmp = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							$out[$key] = ( isset( $tmp['file'] ) ? $tmp['file'] : array() );
						}
						else
						{
							$out[$key]['error'] = array(
								'code'    => 500,
								'message' => "Failed to create file $fullPathName.\n$error"
							);
						}
					}
					$result = array( 'file' => $out );
				}
			}
			else
			{
				// possibly xml or json post either of files or folders to create, copy or move
				$fileUrl = Utilities::getArrayValue( 'url', $_REQUEST, '' );
				if ( !empty( $fileUrl ) )
				{
					// upload a file from a url, could be expandable zip
					$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
					try
					{
						$result = $this->handleFile( $path, '', $tmpName, '', $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					try
					{
						$data = Utilities::getPostDataAsArray();
						if ( empty( $data ) )
						{
							// create folder from resource path
							$this->createFolder( $path );
							$result = array( 'folder' => array( array( 'path' => $path ) ) );
						}
						else
						{
							$out = array( 'folder' => array(), 'file' => array() );
							$folders = Utilities::getArrayValue( 'folder', $data, null );
							if ( empty( $folders ) )
							{
								$folders = ( isset( $data['folders']['folder'] ) ? $data['folders']['folder'] : null );
							}
							if ( !empty( $folders ) )
							{
								if ( !isset( $folders[0] ) )
								{
									// single folder, make into array
									$folders = array( $folders );
								}
								foreach ( $folders as $key => $folder )
								{
									$name = Utilities::getArrayValue( 'name', $folder, '' );
									if ( isset( $folder['source_path'] ) )
									{
										// copy or move
										$srcPath = $folder['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name . '/';
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->copyFolder( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $folder, false ) );
											if ( $deleteSource )
											{
												$this->deleteFolder( $srcPath, true );
											}
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									else
									{
										$fullPathName = $path . $name;
										$content = Utilities::getArrayValue( 'content', $folder, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $folder, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->createFolder( $fullPathName, true, $content );
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$files = Utilities::getArrayValue( 'file', $data, null );
							if ( empty( $files ) )
							{
								$files = ( isset( $data['files']['file'] ) ? $data['files']['file'] : null );
							}
							if ( !empty( $files ) )
							{
								if ( !isset( $files[0] ) )
								{
									// single file, make into array
									$files = array( $files );
								}
								foreach ( $files as $key => $file )
								{
									$name = Utilities::getArrayValue( 'name', $file, '' );
									if ( isset( $file['source_path'] ) )
									{
										// copy or move
										$srcPath = $file['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->copyFile( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $file, false ) );
											if ( $deleteSource )
											{
												$this->deleteFile( $srcPath );
											}
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									elseif ( isset( $file['content'] ) )
									{
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										$content = Utilities::getArrayValue( 'content', $file, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $file, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										try
										{
											$this->writeFile( $fullPathName, $content );
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$result = $out;
						}
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to create folders or files from request.\n{$ex->getMessage()}" );
					}
				}
			}
		}
		else
		{
			// if ending in file name, create the file or folder
			$name = substr( $path, strripos( $path, '/' ) + 1 );
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
				if ( 0 !== strcasecmp( $name, $x_file_name ) )
				{
					throw new Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in write file $path to storage." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create file $path.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
				if ( 0 !== strcasecmp( $name, $x_folder_name ) )
				{
					throw new Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->createFolder( $path, true, $content );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create file $path.\n{$ex->getMessage()}" );
				}
				$result = array( 'folder' => array( array( 'name' => $name, 'path' => $path ) ) );
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multipart/form-data post, should be single file
				$files = $_FILES['files'];
				//$files = Utilities::reorgFilePostArray($files);
				if ( 1 < count( $files['error'] ) )
				{
					throw new Exception( "Multiple files uploaded to a single REST resource '$name'." );
				}
				$name = $files['name'][0];
				$fullPathName = $path;
				$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
				$error = $files['error'][0];
				if ( UPLOAD_ERR_OK == $error )
				{
					$tmpName = $files["tmp_name"][0];
					$contentType = $files['type'][0];
					try
					{
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to create file $fullPathName.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					throw new Exception( "Failed to upload file $name.\n$error" );
				}
			}
			else
			{
				// possibly xml or json post either of file or folder to create, copy or move
				try
				{
					$data = Utilities::getPostDataAsArray();
					error_log( print_r( $data, true ) );
					//$this->addFiles($path, $data['files']);
					$result = array();
				}
				catch ( Exception $ex )
				{

				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->checkPermission( 'update' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		$result = array();
		// possible file handling parameters
		$extract = Utilities::boolval( Utilities::getArrayValue( 'extract', $_REQUEST, false ) );
		$clean = Utilities::boolval( Utilities::getArrayValue( 'clean', $_REQUEST, false ) );
		$checkExist = false;
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// if ending in '/' then create files or folders in the directory
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				// html5 single posting for file create
				$name = $_SERVER['HTTP_X_FILE_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in update file $fullPathName." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				// html5 single posting for folder create
				$name = $_SERVER['HTTP_X_FOLDER_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->createFolder( $fullPathName, true, $content, true );
					$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multi-part/form-data post, single or multiple files
				$files = $_FILES['files'];
				if ( !is_array( $files['error'] ) )
				{
					// single file
					$name = $files['name'];
					$fullPathName = $path . $name;
					$error = $files['error'];
					if ( $error == UPLOAD_ERR_OK )
					{
						$tmpName = $files['tmp_name'];
						$contentType = $files['type'];
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					else
					{
						$result = array(
							'code'    => 500,
							'message' => "Failed to update file $fullPathName.\n$error"
						);
					}
				}
				else
				{
					$out = array();
					//$files = Utilities::reorgFilePostArray($files);
					foreach ( $files['error'] as $key => $error )
					{
						$name = $files['name'][$key];
						$fullPathName = $path . $name;
						if ( $error == UPLOAD_ERR_OK )
						{
							$tmpName = $files['tmp_name'][$key];
							$contentType = $files['type'][$key];
							$tmp = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							$out[$key] = ( isset( $tmp['file'] ) ? $tmp['file'] : array() );
						}
						else
						{
							$out[$key]['error'] = array(
								'code'    => 500,
								'message' => "Failed to update file $fullPathName.\n$error"
							);
						}
					}
					$result = array( 'file' => $out );
				}
			}
			else
			{
				$fileUrl = Utilities::getArrayValue( 'url', $_REQUEST, '' );
				if ( !empty( $fileUrl ) )
				{
					// upload a file from a url, could be expandable zip
					$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
					try
					{
						$result = $this->handleFile( $path, '', $tmpName, '', $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					try
					{
						$data = Utilities::getPostDataAsArray();
						if ( empty( $data ) )
						{
							// create folder from resource path
							$this->createFolder( $path );
							$result = array( 'folder' => array( array( 'path' => $path ) ) );
						}
						else
						{
							$out = array( 'folder' => array(), 'file' => array() );
							$folders = Utilities::getArrayValue( 'folder', $data, null );
							if ( empty( $folders ) )
							{
								$folders = ( isset( $data['folders']['folder'] ) ? $data['folders']['folder'] : null );
							}
							if ( !empty( $folders ) )
							{
								if ( !isset( $folders[0] ) )
								{
									// single folder, make into array
									$folders = array( $folders );
								}
								foreach ( $folders as $key => $folder )
								{
									$name = Utilities::getArrayValue( 'name', $folder, '' );
									if ( isset( $folder['source_path'] ) )
									{
										// copy or move
										$srcPath = $folder['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name . '/';
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->copyFolder( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $folder, false ) );
											if ( $deleteSource )
											{
												$this->deleteFolder( $srcPath, true );
											}
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									else
									{
										$fullPathName = $path . $name;
										$content = Utilities::getArrayValue( 'content', $folder, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $folder, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->createFolder( $fullPathName, true, $content );
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$files = Utilities::getArrayValue( 'file', $data, null );
							if ( empty( $files ) )
							{
								$files = ( isset( $data['files']['file'] ) ? $data['files']['file'] : null );
							}
							if ( !empty( $files ) )
							{
								if ( !isset( $files[0] ) )
								{
									// single file, make into array
									$files = array( $files );
								}
								foreach ( $files as $key => $file )
								{
									$name = Utilities::getArrayValue( 'name', $file, '' );
									if ( isset( $file['source_path'] ) )
									{
										// copy or move
										$srcPath = $file['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->copyFile( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $file, false ) );
											if ( $deleteSource )
											{
												$this->deleteFile( $srcPath );
											}
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									elseif ( isset( $file['content'] ) )
									{
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										$content = Utilities::getArrayValue( 'content', $file, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $file, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										try
										{
											$this->writeFile( $fullPathName, $content );
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$result = $out;
						}
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
			}
		}
		else
		{
			// if ending in file name, update the file or folder
			$name = substr( $path, strripos( $path, '/' ) + 1 );
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
				if ( 0 !== strcasecmp( $name, $x_file_name ) )
				{
					throw new Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in write file $path to storage." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $path.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
				if ( 0 !== strcasecmp( $name, $x_folder_name ) )
				{
					throw new Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->updateFolderProperties( $path, $content );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $path.\n{$ex->getMessage()}" );
				}
				$result = array( 'folder' => array( array( 'name' => $name, 'path' => $path ) ) );
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multipart/form-data post, should be single file
				$files = $_FILES['files'];
				//$files = Utilities::reorgFilePostArray($files);
				if ( 1 < count( $files['error'] ) )
				{
					throw new Exception( "Multiple files uploaded to a single REST resource '$name'." );
				}
				$name = $files['name'][0];
				$fullPathName = $path;
				$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
				$error = $files['error'][0];
				if ( UPLOAD_ERR_OK == $error )
				{
					$tmpName = $files["tmp_name"][0];
					$contentType = $files['type'][0];
					try
					{
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					throw new Exception( "Failed to upload file $name.\n$error" );
				}
			}
			else
			{
				// possibly xml or json post either of file or folder to create, copy or move
				try
				{
					$data = Utilities::getPostDataAsArray();
					error_log( print_r( $data, true ) );
					//$this->addFiles($path, $data['files']);
					$result = array();
				}
				catch ( Exception $ex )
				{

				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->checkPermission( 'update' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		$result = array();
		// possible file handling parameters
		$extract = Utilities::boolval( Utilities::getArrayValue( 'extract', $_REQUEST, false ) );
		$clean = Utilities::boolval( Utilities::getArrayValue( 'clean', $_REQUEST, false ) );
		$checkExist = false;
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// if ending in '/' then create files or folders in the directory
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				// html5 single posting for file create
				$name = $_SERVER['HTTP_X_FILE_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in update file $fullPathName." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				// html5 single posting for folder create
				$name = $_SERVER['HTTP_X_FOLDER_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->createFolder( $fullPathName, true, $content, true );
					$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multi-part/form-data post, single or multiple files
				$files = $_FILES['files'];
				if ( !is_array( $files['error'] ) )
				{
					// single file
					$name = $files['name'];
					$fullPathName = $path . $name;
					$error = $files['error'];
					if ( $error == UPLOAD_ERR_OK )
					{
						$tmpName = $files['tmp_name'];
						$contentType = $files['type'];
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					else
					{
						$result = array(
							'code'    => 500,
							'message' => "Failed to update file $fullPathName.\n$error"
						);
					}
				}
				else
				{
					$out = array();
					//$files = Utilities::reorgFilePostArray($files);
					foreach ( $files['error'] as $key => $error )
					{
						$name = $files['name'][$key];
						$fullPathName = $path . $name;
						if ( $error == UPLOAD_ERR_OK )
						{
							$tmpName = $files['tmp_name'][$key];
							$contentType = $files['type'][$key];
							$tmp = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							$out[$key] = ( isset( $tmp['file'] ) ? $tmp['file'] : array() );
						}
						else
						{
							$out[$key]['error'] = array(
								'code'    => 500,
								'message' => "Failed to update file $fullPathName.\n$error"
							);
						}
					}
					$result = array( 'file' => $out );
				}
			}
			else
			{
				// possibly xml or json post either of files or folders to create, copy or move
				$fileUrl = Utilities::getArrayValue( 'url', $_REQUEST, '' );
				if ( !empty( $fileUrl ) )
				{
					// upload a file from a url, could be expandable zip
					$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
					try
					{
						$result = $this->handleFile( $path, '', $tmpName, '', $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					try
					{
						$data = Utilities::getPostDataAsArray();
						if ( empty( $data ) )
						{
							// create folder from resource path
							$this->createFolder( $path );
							$result = array( 'folder' => array( array( 'path' => $path ) ) );
						}
						else
						{
							$out = array( 'folder' => array(), 'file' => array() );
							$folders = Utilities::getArrayValue( 'folder', $data, null );
							if ( empty( $folders ) )
							{
								$folders = ( isset( $data['folders']['folder'] ) ? $data['folders']['folder'] : null );
							}
							if ( !empty( $folders ) )
							{
								if ( !isset( $folders[0] ) )
								{
									// single folder, make into array
									$folders = array( $folders );
								}
								foreach ( $folders as $key => $folder )
								{
									$name = Utilities::getArrayValue( 'name', $folder, '' );
									if ( isset( $folder['source_path'] ) )
									{
										// copy or move
										$srcPath = $folder['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name . '/';
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->copyFolder( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $folder, false ) );
											if ( $deleteSource )
											{
												$this->deleteFolder( $srcPath, true );
											}
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									else
									{
										$fullPathName = $path . $name;
										$content = Utilities::getArrayValue( 'content', $folder, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $folder, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->createFolder( $fullPathName, true, $content );
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$files = Utilities::getArrayValue( 'file', $data, null );
							if ( empty( $files ) )
							{
								$files = ( isset( $data['files']['file'] ) ? $data['files']['file'] : null );
							}
							if ( !empty( $files ) )
							{
								if ( !isset( $files[0] ) )
								{
									// single file, make into array
									$files = array( $files );
								}
								foreach ( $files as $key => $file )
								{
									$name = Utilities::getArrayValue( 'name', $file, '' );
									if ( isset( $file['source_path'] ) )
									{
										// copy or move
										$srcPath = $file['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->copyFile( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $file, false ) );
											if ( $deleteSource )
											{
												$this->deleteFile( $srcPath );
											}
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									elseif ( isset( $file['content'] ) )
									{
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										$content = Utilities::getArrayValue( 'content', $file, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $file, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										try
										{
											$this->writeFile( $fullPathName, $content );
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$result = $out;
						}
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
			}
		}
		else
		{
			// if ending in file name, create the file or folder
			$name = substr( $path, strripos( $path, '/' ) + 1 );
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
				if ( 0 !== strcasecmp( $name, $x_file_name ) )
				{
					throw new Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in write file $path to storage." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $path.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
				if ( 0 !== strcasecmp( $name, $x_folder_name ) )
				{
					throw new Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->updateFolderProperties( $path, $content );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $path.\n{$ex->getMessage()}" );
				}
				$result = array( 'folder' => array( array( 'name' => $name, 'path' => $path ) ) );
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multipart/form-data post, should be single file
				$files = $_FILES['files'];
				//$files = Utilities::reorgFilePostArray($files);
				if ( 1 < count( $files['error'] ) )
				{
					throw new Exception( "Multiple files uploaded to a single REST resource '$name'." );
				}
				$name = $files['name'][0];
				$fullPathName = $path;
				$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
				$error = $files['error'][0];
				if ( UPLOAD_ERR_OK == $error )
				{
					$tmpName = $files["tmp_name"][0];
					$contentType = $files['type'][0];
					try
					{
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					throw new Exception( "Failed to upload file $name.\n$error" );
				}
			}
			else
			{
				// possibly xml or json post either of file or folder to create, copy or move
				try
				{
					$data = Utilities::getPostDataAsArray();
					error_log( print_r( $data, true ) );
					//$this->addFiles($path, $data['files']);
					$result = array();
				}
				catch ( Exception $ex )
				{

				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->checkPermission( 'delete' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// delete directory of files and the directory itself
			$force = Utilities::boolval( Utilities::getArrayValue( 'force', $_REQUEST, false ) );
			// multi-file or folder delete via post data
			try
			{
				$content = Utilities::getPostDataAsArray();
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to delete storage folders.\n{$ex->getMessage()}" );
			}
			if ( empty( $content ) )
			{
				if ( empty( $path ) )
				{
					throw new Exception( "Empty file or folder path given for storage delete." );
				}
				try
				{
					$this->deleteFolder( $path, $force );
					$result = array( 'folder' => array( array( 'path' => $path ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to delete storage folder '$path'.\n{$ex->getMessage()}" );
				}
			}
			else
			{
				try
				{
					$out = array();
					if ( isset( $content['file'] ) )
					{
						$files = $content['file'];
						$out['file'] = $this->deleteFiles( $files, $path );
					}
					if ( isset( $content['folder'] ) )
					{
						$folders = $content['folder'];
						$out['folder'] = $this->deleteFolders( $folders, $path, $force );
					}
					$result = $out;
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to delete storage folders.\n{$ex->getMessage()}" );
				}
			}
		}
		else
		{
			// delete file from permanent storage
			try
			{
				$this->deleteFile( $path );
				$result = array( 'file' => array( array( 'path' => $path ) ) );
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to delete storage file '$path'.\n{$ex->getMessage()}" );
			}
		}

		return $result;
	}

	/**
	 * @throw Exception
	 */
	abstract public function checkContainerForWrite();

	/**
	 * @param $path
	 *
	 * @return bool
	 * @throw Exception
	 */
	abstract public function folderExists( $path );

	/**
	 * @param string $path
	 * @param bool   $include_files
	 * @param bool   $include_folders
	 * @param bool   $full_tree
	 *
	 * @return array
	 * @throws Exception
	 */
	abstract public function getFolderContent( $path, $include_files = true, $include_folders = true, $full_tree = false );

	/**
	 * @param $path
	 *
	 * @return array
	 * @throws Exception
	 */
	abstract public function getFolderProperties( $path );

	/**
	 * @param string $path
	 * @param array  $properties
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function createFolder( $path, $properties = array() );

	/**
	 * @param string $path
	 * @param string $properties
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function updateFolderProperties( $path, $properties = '' );

	/**
	 * @param string $dest_path
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function copyFolder( $dest_path, $src_path, $check_exist = false );

	/**
	 * @param string $path Folder path relative to the service root directory
	 * @param  bool  $force
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function deleteFolder( $path, $force = false );

	/**
	 * @param array  $folders Array of folder paths that are relative to the root directory
	 * @param string $root    directory from which to delete
	 * @param  bool  $force
	 *
	 * @return array
	 * @throws Exception
	 */
	abstract public function deleteFolders( $folders, $root = '', $force = false );

	/**
	 * @param $path
	 *
	 * @return bool
	 * @throws Exception
	 */
	abstract public function fileExists( $path );

	/**
	 * @param         $path
	 * @param  string $local_file
	 * @param  bool   $content_as_base
	 *
	 * @return string
	 * @throws Exception
	 */
	abstract public function getFileContent( $path, $local_file = '', $content_as_base = true );

	/**
	 * @param       $path
	 * @param  bool $include_content
	 * @param  bool $content_as_base
	 *
	 * @return array
	 * @throws Exception
	 */
	abstract public function getFileProperties( $path, $include_content = false, $content_as_base = true );

	/**
	 * @param string $path
	 *
	 * @return null
	 */
	abstract public function streamFile( $path );

	/**
	 * @param string $path
	 *
	 * @return null
	 */
	abstract public function downloadFile( $path );

	/**
	 * @param string  $path
	 * @param string  $content
	 * @param boolean $content_is_base
	 * @param bool    $check_exist
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function writeFile( $path, $content, $content_is_base = true, $check_exist = false );

	/**
	 * @param string $path
	 * @param string $local_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function moveFile( $path, $local_path, $check_exist = false );

	/**
	 * @param string $dest_path
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function copyFile( $dest_path, $src_path, $check_exist = false );

	/**
	 * @param $path File path relative to the service root directory
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function deleteFile( $path );

	/**
	 * @param array  $files Array of file paths relative to root
	 * @param string $root
	 *
	 * @return array
	 * @throws Exception
	 */
	abstract public function deleteFiles( $files, $root = '' );

	/**
	 * @param            $path
	 * @param ZipArchive $zip
	 * @param bool       $clean
	 * @param string     $drop_path
	 *
	 * @return array
	 * @throws Exception
	 */
	abstract public function extractZipFile( $path, $zip, $clean = false, $drop_path = '' );

	/**
	 * @param string          $path
	 * @param null|ZipArchive $zip
	 * @param string          $zipFileName
	 * @param bool            $overwrite
	 *
	 * @return string Zip File Name created/updated
	 */
	abstract public function getFolderAsZip( $path, $zip = null, $zipFileName = '', $overwrite = false );

}