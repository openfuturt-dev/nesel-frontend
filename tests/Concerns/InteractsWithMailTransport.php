<?php

namespace Tests\Concerns;

use Closure;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Sends mail through a real Symfony transport whose behaviour the test controls,
 * unlike Mail::fake(), which never reaches the transport layer.
 */
trait InteractsWithMailTransport
{
    /**
     * @param  Closure(SentMessage): void  $onSend
     */
    protected function useMailTransport(Closure $onSend): void
    {
        Mail::extend('test', fn () => new class($onSend) extends AbstractTransport
        {
            public function __construct(private Closure $onSend)
            {
                parent::__construct();
            }

            protected function doSend(SentMessage $message): void
            {
                ($this->onSend)($message);
            }

            public function __toString(): string
            {
                return 'test://';
            }
        });

        config()->set('mail.mailers.test', ['transport' => 'test']);
        config()->set('mail.default', 'test');
        Mail::purge('test');
    }

    /**
     * Fail every send the way an unreachable SMTP server does.
     */
    protected function useFailingMailer(?TransportException $exception = null): void
    {
        $this->useMailTransport(fn () => throw $exception ?? new TransportException('Connection could not be established with host "mail.ne-sel.com:465".'));
    }
}
