<?php

class LiveVolt extends \Phalcon\Mvc\View\Engine\Volt
{
	protected $paths = array();
	protected $cachedTemplatePath = array();
	protected $globals = array();

	public function getCompiler()
	{
		if (empty($this->_compiler))
		{
			$GLOBALS['volt'] = $this;

			$this->_compiler = new LiveVoltCompiler($this->getView());
			$this->_compiler->setOptions($this->getOptions());
			$this->_compiler->setDI($this->getDI());

			$this->_compiler->addFunction('empty', 'empty');

			$this->_compiler->addFunction('config', function($resolvedArgs, $exprArgs) {
				return '$volt->getDI()->get(\'config\')->get(' . $resolvedArgs . ')';
			});

			$this->_compiler->addFunction('global', function($resolvedArgs, $exprArgs) {
				return '$volt->setOrReturnGlobal(' . $resolvedArgs . ')';
			});

			$this->_compiler->addFunction('title', function($resolvedArgs, $exprArgs) {
				return $resolvedArgs . ($resolvedArgs ? ' . ' : '') . ' $volt->setOrReturnGlobal(\'pageTitle\'' . ($resolvedArgs ? ', ' : '') . $resolvedArgs . ')';
			});

			$this->_compiler->addFunction('t', function($resolvedArgs, $exprArgs) {
				return '$volt->getDI()->get(\'application\')->translate(' . $resolvedArgs . ')';
			});

			$this->_compiler->addFunction('tip', function($resolvedArgs, $exprArgs) {
				return '$volt->getDI()->get(\'application\')->translate(' . $resolvedArgs . ')';
			});

			$this->_compiler->addFunction('req', function($resolvedArgs, $exprArgs) {
				return '$volt->getDI()->get(\'request\')->get(' . $resolvedArgs . ')';
			});

			$this->_compiler->addFunction('param', function($resolvedArgs, $exprArgs) {
				return '(!empty($params[' . $resolvedArgs . ']) ? $params[' . $resolvedArgs . '] : \'\')';
			});
		}

		return $this->_compiler;
	}

	public function render($templatePath, $params, $mustClean = null)
	{
		$path = $this->getTemplatePath($this->getRelativeTemplatePath($templatePath));

		return parent::render($path ? $path : $templatePath, $params, $mustClean);
	}

	public function partial($partialPath, $params = array())
	{
		return parent::render($this->getTemplatePath($partialPath), array_merge($this->globals, $params));
	}

	public function setOrReturnGlobal($key, $value = null)
	{
		if ($value)
		{
			$this->globals[$key] = $value;
		}
		else
		{
			return isset($this->globals[$key]) ? $this->globals[$key] : '';
		}
	}

	public function getGlobals()
	{
		return $this->globals;
	}

	public function getTemplatePaths($template = '')
	{
		if (!$this->paths)
		{
			if ($theme = $this->application->getTheme())
			{
				$this->paths = array_merge($this->paths, $this->getThemePaths($theme));
			}

			$this->paths[] = $this->config->getPath('storage/customize/view/');
			$this->paths[] = $this->config->getPath('application/view/');
			$this->paths[] = dirname($this->config->getPath('module'));
		}

		if (!$template)
		{
			return $this->paths;
		}

		$paths = $this->paths;

		foreach ($paths as &$path)
		{
			$path = $this->getPath($path, $template);
		}

		return $paths;
	}

	public function resetPaths()
	{
		$this->paths = array();
	}

	public function getTemplatePath($template)
	{
		if (!isset($this->cachedTemplatePath[$template]))
		{
			foreach ($this->getTemplatePaths($template) as $path)
			{
				if (is_readable($path))
				{
					$this->cachedTemplatePath[$template] = $path;
					break;
				}
			}

			if (!isset($this->cachedTemplatePath[$template]))
			{
				$this->cachedTemplatePath[$template] = null;
			}
		}

		return $this->cachedTemplatePath[$template];
	}

	public function getBaseTemplatePath($tplName)
	{
		$tplName = substr($tplName, 1);
		foreach (array($this->config->getPath('storage/customize/view/'), $this->config->getPath('application/view/')) as $path)
		{
			$file = $path . $tplName;
			if (file_exists($file))
			{
				return $file;
			}
		}
	}

	public function getRelativeTemplatePath($template)
	{
		$template = str_replace('\\', '/', $template);
		if (preg_match('/\/(module\/.*\/.*)/', $template, $match))
		{
			preg_match('/\/(module\/.*)/', $template, $match);
			return str_replace('application/view/', '', $match[1]);
		}

		foreach (array('application/view', 'storage/customize/view') as $path)
		{
			$path = $this->config->getPath($path);
			$path = str_replace('\\', '/', $path);

			if (substr($template, 0, strlen($path)) == $path)
			{
				return substr($template, strlen($path) + 1);
			}
		}
	}

	private function getPath($root, $template)
	{
		if (substr($template, 0, 7) == 'module/')
		{
			if ($this->paths[count($this->paths) - 1] == $root)
			{
				$root = dirname($this->config->getPath('module'));
				$template = preg_replace('/module\/([-a-zA-Z0-9_]+)\/(.*)/', 'module/\\1/application/view/\\2', $template);
			}
		}

		return $root . '/' . $template;
	}

	private function getThemePaths($theme, $includedThemes = array())
	{
		$paths = $inheritConf = array();
		$paths[] = $this->config->getPath('storage/customize/view/theme/' . $theme . '.');
		$paths[] = $this->config->getPath('application/view/theme/' . $theme . '.');

		$inheritConf[] = $this->config->getPath('storage/customize/view/theme/' . $theme . '/inherit') . '.php';
		$inheritConf[] = $this->config->getPath('application/view/theme/' . $theme . '/inherit') . '.php';

		foreach ($inheritConf as $inherit)
		{
			if (file_exists($inherit))
			{
				break;
			}
		}

		if (file_exists($inherit))
		{
			$inherited = include $inherit;
			if (!is_array($inherited))
			{
				$inherited = array($inherited);
			}

			foreach ($inherited as $parent)
			{
				if (empty($includedThemes[$parent]))
				{
					$includedThemes[$parent] = true;
					$paths = array_merge($paths, $this->getThemePaths($parent, $includedThemes));
				}
			}
		}

		return $paths;
	}
}

class LiveVoltCompiler extends \Phalcon\Mvc\View\Engine\Volt\Compiler
{
	protected function _compileSource($source, $something = null)
	{
		$source = str_replace('{{', '<' . '?php $ng = <<<NG' . "\n" . '\x7B\x7B', $source);
		$source = str_replace('}}', '\x7D\x7D' . "\n" . 'NG;' . "\n" . ' echo $ng; ?' . '>', $source);

		$source = str_replace('[[', '{{', $source);
		$source = str_replace(']]', '}}', $source);

		$source = preg_replace('/{t ([^\|]+?)}/', '[[t("$1")]]', $source);
		$source = preg_replace('/{tn ([^\|]+?)}/', '[[t("$1", true)]]', $source);

		$source = preg_replace('/\{\% title \%\}(.*)\{\% endblock \%\}/', '{% block title %}{{ title(\'$1\') }}{% endblock %}', $source);

		//$source = '<' . '?php extract($this->getGlobals()); ?' . '>' . $source;

		$variable = '<' . '?php $volt = $GLOBALS[\'volt\']; ?' . '>';
		$compiled = parent::_compileSource($source, $something);

		if (is_array($compiled))
		{
			$compiled[0] = $variable . $compiled[0];
			foreach ($compiled as &$entry)
			{
				$entry = $this->replaceThis($entry);
			}
		}
		else
		{
			$compiled = $this->replaceThis($variable . $compiled);
		}
/*
*/
		//var_dump($compiled);exit;

		return $compiled;
	}

	protected function replaceThis ($source)
	{
		if (is_array($source))
		{
			return $source;
		}

		$source = str_replace('$this', '$volt', $source);
		$source = str_replace('$__this', '$this', $source);
		$source = preg_replace('/function vmacro_([^\)]+)\)[\s]+\{/', 'function vmacro_$1) { global $volt; ', $source);

		$source = preg_replace('/throw new \\\Phalcon\\\Mvc\\\View\\\Exception\("Macro [\_a-zA-Z0-9]+ was called without parameter: ([\_a-zA-Z0-9]+)"\)\;/', '\$$1 = \'\';', $source);

		return $source;
	}

}