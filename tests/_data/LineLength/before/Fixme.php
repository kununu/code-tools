<?php
declare(strict_types=1);

namespace Kununu;

use Some\Very\Long\Namespace\Path\That\Definitely\Exceeds\The\Maximum\Line\Length\Of\One\Hundred\And\Twenty\Characters\In\Total;

class Fixme
{
    public const VERY_BIG_STRING = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam nec nisl nec nunc tincidunt tincidunt. Nullam nec nisl nec nunc tincidunt tincidunt.';

    public function someMethod(): void
    {
        $veryBigString = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam nec nisl nec nunc tincidunt tincidunt. Nullam nec nisl nec nunc tincidunt tincidunt.';

        if ($this->someOtherMethodWithAVeryBigNameThatIsCloseToHundredCharactersInLength() && $veryBigString === self::VERY_BIG_STRING) {
            // do something
        }
    }

    public function someOtherMethodWithAVeryBigNameThatIsCloseToHundredCharactersInLength(): bool
    {
        return true;
    }
}
