<?php
/*
 * PHP client for BaseX.
 * Works with BaseX 7.0 and later
 *
 * Documentation: https://docs.basex.org/wiki/Clients
 *
 * (C) BaseX Team 2005-22, BSD License
 */

namespace Caxy\BaseX;

class Query implements \Iterator
{
    protected Session $session;
    protected string $id;
    protected ?array $cache = null;
    protected int $pos = 0;

    /**
     * Query constructor.
     *
     * @param Session $session
     * @param string $query
     */
    public function __construct(Session $session, string $query)
    {
        $this->session = $session;
        $this->id = $this->exec(chr(0), $query);
    }

    public function bind(string $name, string $value, string $type = ""): void
    {
        $this->exec(chr(3), $this->id.chr(0).$name.chr(0).$value.chr(0).$type);
    }

    public function context(string $value, string $type = ""): void
    {
        $this->exec(chr(14), $this->id.chr(0).$value.chr(0).$type);
    }

    public function execute(): string
    {
        return $this->exec(chr(5), $this->id);
    }

    public function more(): bool
    {
        if ($this->cache === null) {
            $this->pos = 0;
            $this->session->send(chr(4).$this->id.chr(0));
            while (!$this->session->ok()) {
                $this->cache[] = $this->session->readString();
            }
            if (!$this->session->ok()) {
                throw new BaseXException($this->session->readString());
            }
        }
        if ($this->pos < count($this->cache)) {
            return true;
        }
        $this->cache = null;
        return false;
    }

    public function next(): void
    {
        if ($this->more()) {
            $this->pos++;
        }
    }

    public function info(): string
    {
        return $this->exec(chr(6), $this->id);
    }

    public function options(): string
    {
        return $this->exec(chr(7), $this->id);
    }

    public function close(): void
    {
        $this->exec(chr(2), $this->id);
    }

    public function exec(string $cmd, string $arg): string
    {
        $this->session->send($cmd.$arg);
        $s = $this->session->receive();
        if ($this->session->ok() !== true) {
            throw new BaseXException($this->session->readString());
        }
        return $s;
    }

    public function current(): mixed
    {
        return $this->cache[$this->pos];
    }

    public function key(): mixed
    {
        return $this->pos;
    }

    public function valid(): bool
    {
        return $this->more();
    }

    public function rewind(): void
    {
    }
}
