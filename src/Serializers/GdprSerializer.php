<?php

namespace Cerpus\Gdpr\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class GdprSerializer extends ArraySerializer
{
    /**
     * Serialize a collection.
     *
     * @param string $resourceKey
     * @param array $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data)
    {
        if (count($data) === 1) {
            return $resourceKey ?: $data[0];
        }

        return $resourceKey ?: $data;
    }
}
