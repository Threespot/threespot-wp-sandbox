<?php


namespace MapSVG;


class FilesRepository
{

	public $readFolders;
	public $writeToFolder;
	public $modelClass = 'File';
	public $fileTypes = array('svg');

	public function __construct($readFolders = null, $writeToFolder = null, $fileTypes = array('svg'))
	{
		$this->readFolders = $readFolders;
		$this->writeToFolder = $writeToFolder;
		$this->fileTypes = $fileTypes;
	}

	public function getModelClass()
	{
		return __NAMESPACE__ . '\\' . $this->modelClass;
	}

	public function newFile($data)
	{
		$class = $this->getModelClass();
		return new $class($data);
	}

	/**
	 * Get the list of all SVG files
	 *
	 * @return array List of the maps
	 */
	public function find()
	{

		$files = array();

		try {
			foreach ($this->readFolders as $folder) {
				if (is_dir($folder)) {
					foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder)) as $filename) {
						if (in_array(pathinfo($filename, PATHINFO_EXTENSION), $this->fileTypes)) {
							$files[] = $this->newFile(array(
								"serverPath" => $filename
							));
						}
					}
				}
			}

			sort($files);
		} catch (\InvalidArgumentException $e) {
			Logger::error($e);
		}

		return $files;
	}

	public function checkUploadDir()
	{
		$mapsvg_error = false;
		if (!file_exists($this->writeToFolder)) {
			if (!wp_mkdir_p($this->writeToFolder))
				$mapsvg_error = "Unable to create directory " . $this->writeToFolder . ". Is its parent directory writable by the server?";
		} else {
			if (!wp_is_writable($this->writeToFolder))
				$mapsvg_error = $this->writeToFolder . " is not writable. Please change the folder permissions.";
		}
		return $mapsvg_error;
	}

	/**
	 * Updates SVG file edits (the function is used by "Edit SVG file" mode in MapSVG)
	 * @param FileInterface $file
	 * @return FileInterface
	 * @throws \Exception
	 */
	function update($file)
	{
		$this->save($file);
		return $file;
	}


	/**
	 * Make a copy of one of the included SVG files, which are "read-only", put the copy to "uploads" folder
	 * so users could edit the SVG file.
	 * @param FileInterface $file
	 * @return FileInterface
	 * @throws \Exception
	 */
	public function copy($file)
	{

		$actual_name = pathinfo($file->name, PATHINFO_FILENAME);
		$original_name = $actual_name;
		$extension = pathinfo($file->name, PATHINFO_EXTENSION);

		$i = 1;
		$last_exising_number = 1;

		while (file_exists($this->writeToFolder . DIRECTORY_SEPARATOR . $actual_name . "." . $extension)) {
			if (strpos($actual_name, '_') !== false) {
				$parts = explode('_', $actual_name);
				$number = array_pop($parts);
				if (is_numeric($number)) {
					$number = (int)$number;
					$last_exising_number = $number + 1;
				}
				$parts[] = $last_exising_number;
				$actual_name = implode('_', $parts);
				$i++;
			} else {
				$actual_name = (string)$original_name . '_' . $i;
				$i++;
			}
		}

		$newfile =  $this->writeToFolder . DIRECTORY_SEPARATOR . $actual_name . "." . $extension;

		list($junk, $important_stuff) = explode(basename(WP_CONTENT_DIR), $file->relativeUrl);
		$fileNameFrom = WP_CONTENT_DIR . $important_stuff;

		$error = $this->checkUploadDir();

		if (!$error) {
			if (!copy($fileNameFrom, $newfile)) {
				$error = "Failed to copy the file";
			}
		}

		if (!$error) {
			return $this->newFile(array('serverPath' => $newfile));
		} else {
			throw new \Exception(wp_kses($error, array()), 400);
		}
	}

	/**
	 * Creates new SVG file
	 * @param FileInterface $file
	 * @returns FileInterface
	 */
	public function create($file)
	{
		$this->save($file);
		return $file;
	}

	/**
	 * @param File  $file
	 * @returns FileInterface
	 * @throws \Exception
	 */
	public function save($file)
	{

		$mapsvg_error = $this->checkUploadDir();

		if (! $mapsvg_error) {
			if (!$file->serverPath) {
				$file->setServerPath($this->writeToFolder . DIRECTORY_SEPARATOR . $file->name);
				$file->updateRelativeUrl();
			}

			// Initialize WP_Filesystem
			global $wp_filesystem;
			if (empty($wp_filesystem)) {
				require_once(ABSPATH . '/wp-admin/includes/file.php');
				WP_Filesystem();
			}

			// Write file using WP_Filesystem
			$res = $wp_filesystem->put_contents(
				$file->serverPath,
				$file->body,
				FS_CHMOD_FILE // Predefined WordPress constant for correct file permissions
			);

			if ($res === false) {
				throw new \Exception('Can\'t write data to the file', 400);
			}
			return $this;
		} else {
			throw new \Exception(esc_html($mapsvg_error), 400);
		}
	}

	/**
	 * @param FileInterface $file
	 * @returns FileInterface
	 * @throws \Exception
	 */
	public function upload($_file)
	{
		$mapsvg_error = $this->checkUploadDir();

		if (!$mapsvg_error) {
			$fileClass = $this->getModelClass();
			$file = new $fileClass(array('serverPath' => $this->writeToFolder . DIRECTORY_SEPARATOR . $_file['name']));

			require_once(ABSPATH . 'wp-admin/includes/file.php');
			WP_Filesystem();
			global $wp_filesystem;

			// Use WordPress's file handling functions
			$copied = $wp_filesystem->copy(
				$_file['tmp_name'],
				$file->serverPath,
				true // Overwrite if exists
			);

			if ($copied) {
				return $file;
			} else {
				throw new \Exception(esc_html__('Can\'t upload the file', 'mapsvg-lite'), 400);
				
			}
		} else {
			throw new \Exception(esc_html($mapsvg_error), 400);
		}
	}
}
