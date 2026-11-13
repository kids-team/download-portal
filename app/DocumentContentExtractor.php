<?php

namespace App;

class DocumentContentExtractor
{
    public function extract(?array $blocks): string
    {
        $texts = [];

        $this->visit($blocks, $texts);

        return implode(' ', $texts);
    }

    /** @param array<int, string> $texts */
    private function visit(mixed $node, array &$texts): void
    {
        if (is_string($node)) {
            $texts[] = $node;

            return;
        }

        if (! is_array($node)) {
            return;
        }

        if (array_is_list($node)) {
            foreach ($node as $child) {
                $this->visit($child, $texts);
            }

            return;
        }

        if (($node['type'] ?? null) === 'text' && is_string($node['text'] ?? null)) {
            $texts[] = $node['text'];

            return;
        }

        $this->visit($node['content'] ?? null, $texts);
        $this->visit($node['children'] ?? null, $texts);
    }
}
