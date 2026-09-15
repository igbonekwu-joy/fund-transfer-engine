<?php

use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Exceptions\Ledger\InvalidTransferException;
use Illuminate\Contracts\Debug\ShouldntReport;

it('defines an insufficient balance exception that should not be reported', function () {
    $exception = new InsufficientBalanceException;

    expect($exception)->toBeInstanceOf(InsufficientBalanceException::class)
        ->and($exception)->toBeInstanceOf(ShouldntReport::class)
        ->and($exception->getMessage())->toBe('Insufficient balance.');
});

it('defines an invalid transfer exception that should not be reported', function () {
    $exception = new InvalidTransferException('Cannot transfer to the same account.');

    expect($exception)->toBeInstanceOf(InvalidTransferException::class)
        ->and($exception)->toBeInstanceOf(ShouldntReport::class)
        ->and($exception->getMessage())->toBe('Cannot transfer to the same account.');
});

it('renders ledger domain exceptions as json validation responses', function () {
    $insufficient = new InsufficientBalanceException;
    $invalid = new InvalidTransferException('Cannot transfer to the same account.');

    $request = request();

    $insufficientResponse = $insufficient->render($request);
    $invalidResponse = $invalid->render($request);

    expect($insufficientResponse->getStatusCode())->toBe(422)
        ->and($insufficientResponse->getData(true))->toBe(['message' => 'Insufficient balance.'])
        ->and($invalidResponse->getStatusCode())->toBe(422)
        ->and($invalidResponse->getData(true))->toBe(['message' => 'Cannot transfer to the same account.']);
});

it('can be thrown and caught by type', function () {
    expect(fn () => throw new InsufficientBalanceException)
        ->toThrow(InsufficientBalanceException::class);

    expect(fn () => throw new InvalidTransferException('Cannot transfer to the same account.'))
        ->toThrow(InvalidTransferException::class, 'Cannot transfer to the same account.');
});
