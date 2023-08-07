<?php
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
    $ecsConfig->sets([SetList::PSR_12, SetList::COMMON,]);

    $ecsConfig->skip([
        // Remove sniff, from common/control-structures
        \PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class,

        // Remove sniff, from common/spaces
        PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer::class,
        \PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class,
        \PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class,
    ]);
};
