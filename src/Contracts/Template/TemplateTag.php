<?php
/**
 * Template tag contract.
 *
 * Template tags can be registered with the template engine so that they are
 * "bolted" onto the object and behave as if they were methods (e.g.,
 * `$engine->tagName()`). Developers can create custom constructors with any
 * number of parameters, required or not, and the engine will pass down those
 * that are input. Essentially, this is just a way of extending the template
 * engine for custom use cases.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Template;

use Stringable;
use Novaris\Contracts\{CastsToHtml, CastsToText};
use Novaris\Tools\Collection;

interface TemplateTag extends CastsToHtml, CastsToText, Stringable
{
	/**
	 * Sets the data for the tag.
	 *
	 * @since 1.0.0
	 */
	public function setData( Collection $data ): void;
}