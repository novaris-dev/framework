<?php
/**
 * Powered by Novaris class.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

// Contracts.
use Novaris\Contracts\{Displayable, Renderable};

class PoweredBy implements Displayable, Renderable
{
	/**
	 * Stores the array of notes.
	 *
	 * @since 1.0.0
	 */
	protected array $superpowers;

	/**
	 * Sets up the object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->superpowers = [
			'Fueled by heartbeats and resilience.',
			'Driven by wild dreams and untamed passion.',
			'Powered by the force that weaves the universe together.',
			'Energized by boundless love.',
			'Fueled by the mystery of the endless abyss.',
			'Crafted by the chaotic brilliance of a visionary.',
			'Sustained by harmony and deep understanding.',
			'Ignited by the finest brew of ambition.',
			'Kept alive by sleepless quests and restless minds.',
			'Driven by love for everything that breathes life.',
			'Powered by something beyond the reach of imagination.',
			'Whispered from futures unknown and yet to come.',
			'Forged by the union of tech and infinite possibility.',
			'Strengthened by kindness, the quiet force of humanity.',
			'Inspired by the unseen melodies of hidden realms.',
			'Empowered by voices that rise above silence.',
			'Celebrating the beauty of the human soul’s infinite depth.',
			'Fueled by the unending thirst for wisdom across time.',
			'Charged by the pulse of undiscovered galaxies.',
			'Illuminated by the everyday magic hidden in plain sight.',
			'Carried by the legacy of ancient wisdom and stories untold.',
			'Danced into existence by the eternal play of light and shadow.',
			'Warmed by the first rays of a new day’s promise.',
			'Guided by the secrets whispered by the depths of the ocean.',
			'Brightened by echoes of joy and pure laughter.',
			'Driven by the tireless pursuit of eternal truth.'
		];
	}

	/**
	 * Displays the message.
	 *
	 * @since 1.0.0
	 */
	public function display(): void
	{
		echo $this->render();
	}

	/**
	 * Returns the message.
	 *
	 * @since 1.0.0
	 */
	public function render(): string
	{
		$collection = $this->superpowers;

		return $collection[ array_rand( $collection, 1 ) ];
	}
}
