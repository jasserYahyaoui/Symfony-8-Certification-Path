<?php

declare(strict_types=1);

/*
 * Deep audit 8.0, gap G2: a configuration node cannot be both required and
 * defaulted (Config 8.0, « Ensure configuration nodes do not have both
 * isRequired() and defaultValue() »).
 */

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;

/** Builds a one-node tree; returns null when it builds, or the exception message. */
$build = static function (callable $define): ?string {
    try {
        $tree = new TreeBuilder('app');
        $define($tree->getRootNode()->children());
        $tree->buildTree();

        return null;
    } catch (InvalidDefinitionException $e) {
        return $e->getMessage();
    }
};

return [
    'config.required-node-has-no-default' => static function () use ($build): ?string {
        $refused = 'The node "x" cannot be required and have a default value.';
        $cases = [
            'isRequired()->defaultValue()' => static fn (NodeBuilder $c) => $c->scalarNode('x')->isRequired()->defaultValue(1)->end(),
            'defaultValue()->isRequired()' => static fn (NodeBuilder $c) => $c->scalarNode('x')->defaultValue(1)->isRequired()->end(),
            'isRequired()->defaultNull()' => static fn (NodeBuilder $c) => $c->scalarNode('x')->isRequired()->defaultNull()->end(),
        ];
        foreach ($cases as $name => $define) {
            if ($refused !== $build($define)) {
                return "$name was not refused";
            }
        }

        return null === $build(static fn (NodeBuilder $c) => $c->scalarNode('x')->isRequired()->end()) ? null : 'isRequired() alone was refused';
    },
];
