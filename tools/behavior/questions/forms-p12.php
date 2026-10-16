<?php

declare(strict_types=1);

/*
 * Remediation P1.2 (2026-10-16): the multiple-answer Forms question, one
 * boolean per choice in content order, compared by run.php with the `correct`
 * flags of the YAML. The Validator extension stands for the full-stack
 * application the question names.
 */

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

return [
    // invalid with an extra entry; ['a', null] | 'b' removed; a prototype in the view
    'QST-gtk4r5hqgvhx' => static function (): array {
        $factory = Forms::createFormFactoryBuilder()->addExtension(new ValidatorExtension(Validation::createValidator()))->getFormFactory();
        $form = static fn () => $factory->createBuilder(FormType::class, ['tags' => ['a', 'b']])
            ->add('tags', CollectionType::class, ['entry_type' => TextType::class])
            ->getForm();
        $extra = $form();
        $extra->submit(['tags' => ['a', 'b', 'c']]);
        $missing = $form();
        $missing->submit(['tags' => ['a']]);

        return [
            !$extra->isValid(),
            ['a', null] === $missing->getData()['tags'],
            ['a'] === $missing->getData()['tags'],
            isset($form()->createView()['tags']->vars['prototype']),
        ];
    },
];
