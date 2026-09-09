<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";
require_once __DIR__ . "/Layout.php";

/**
 * Single responsible module: owns the request→response pipeline behind the
 * page seam (issue #189). Its public interface is deliberately wide — six
 * endpoint methods plus the header/footer and escaping the front controllers
 * still compose against — and it orchestrates six complex page handlers, so
 * the resulting cyclomatic sum and public-method count exceed the PHPMD
 * threshold by design. PHPMD is a Signal producer here (see phpmd.xml.dist),
 * so these structural findings are suppressed rather than forcing a
 * shallower decomposition.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Page
{
    /** @var Layout */
    private $layout;

    public function __construct(Bootstrap $app)
    {
        $this->layout = new Layout($app);
    }

    /** @param mixed $value */
    public function text($value): string
    {
        return $this->layout->text($value);
    }

    /** @param mixed $value */
    public function attr($value): string
    {
        return $this->layout->attr($value);
    }

    public function header(): string
    {
        return $this->layout->header();
    }

    public function footer(): string
    {
        return $this->layout->footer();
    }
}
