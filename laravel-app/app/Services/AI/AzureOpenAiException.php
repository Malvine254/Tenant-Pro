<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * Thrown when the Azure OpenAI service cannot be reached or returns an unusable response.
 */
class AzureOpenAiException extends RuntimeException
{
}
