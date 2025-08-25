<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* modules/contrib/decoupled_preview_iframe/templates/preview-iframe.html.twig */
class __TwigTemplate_8932783a3546c650e7b20fabc53843e1 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield "<div class=\"iframe_information\">
  ";
        // line 2
        if ((($tmp = ($context["showPublishedToggle"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 3
            yield "  <div class=\"js-form-type-checkbox form-type--checkbox form-type--boolean\">
    <input
      id=\"show_published_toggle\"
      type=\"checkbox\"
      class=\"form-checkbox form-boolean form-boolean--type-checkbox\"
    />

    <span class=\"checkbox-toggle\">
      <span class=\"checkbox-toggle__inner\"></span>
    </span>

    <label class=\"form-item__label option\">Show Published</label>
  </div>

  <div>
    <label class=\"form-item__label option\">|</label>
  </div>
  ";
        }
        // line 21
        yield "  <div>
    <label class=\"form-item__label option\">Preview URL:</label>
    <a
      id=\"preview_url_anchor\"
      href=\"";
        // line 25
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["url"] ?? null), "html", null, true);
        yield "\"
      target=\"_blank\"
    >";
        // line 27
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($_v0 = Twig\Extension\CoreExtension::split($this->env->getCharset(), ($context["url"] ?? null), "?")) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0[0] ?? null) : CoreExtension::getAttribute($this->env, $this->source, Twig\Extension\CoreExtension::split($this->env->getCharset(), ($context["url"] ?? null), "?"), 0, [], "array", false, false, true, 27)), "html", null, true);
        yield "</a>
  </div>

</div>
<div class=\"decoupled_preview_iframe-container\">
  <iframe
    id=\"node_preview\"
    class=\"decoupled_preview_iframe\"
    src=\"";
        // line 35
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["url"] ?? null), "html", null, true);
        yield "\"
    frameborder=\"0\"
    allowtransparency=\"true\"
    width=\"100%\"
    sandbox = \"allow-scripts allow-forms allow-same-origin allow-pointer-lock allow-presentation allow-top-navigation\"
  >
  </iframe>
</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["showPublishedToggle", "url"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/decoupled_preview_iframe/templates/preview-iframe.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  91 => 35,  80 => 27,  75 => 25,  69 => 21,  49 => 3,  47 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/decoupled_preview_iframe/templates/preview-iframe.html.twig", "/var/www/html/web/modules/contrib/decoupled_preview_iframe/templates/preview-iframe.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 2];
        static $filters = ["escape" => 25, "split" => 27];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape', 'split'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
