<?php
declare(strict_types=1);

namespace Kununu;

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
