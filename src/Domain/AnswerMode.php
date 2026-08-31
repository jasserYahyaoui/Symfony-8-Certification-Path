<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Master Plan §7.1 `answer_mode`.
 */
enum AnswerMode: string
{
    case Single = 'single';
    case Multiple = 'multiple';
}
