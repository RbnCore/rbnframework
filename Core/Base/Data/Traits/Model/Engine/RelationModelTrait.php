<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

/**
 * RelationModelTrait - Smart ORM Relationship Management 🔗
 */
trait RelationModelTrait
{
    /**
     * İlişkili model örneğini tek dikişten üret 🔗
     *
     * Anayasa §1: çekirdekte doğrudan `new` yasaktır. B-02'de üç ayrı yerde
     * `new $relatedModel()` vardı; hepsi TEK bir yardımcıya toplandı. İoC
     * tabanlı bir model fabrikası bağlanmak istenirse değiştirilecek yer
     * yalnızca burasıdır.
     *
     * @param class-string $relatedModel
     */
    protected function resolveRelationInstance(string $relatedModel): object
    {
        return new $relatedModel();
    }

    /**
     * Define a one-to-many relationship
     */
    public function hasMany(string $relatedModel, ?string $foreignKey = null, ?string $localKey = null)
    {
        $instance = $this->resolveRelationInstance($relatedModel);
        return [
            'type' => 'hasMany',
            'model' => $relatedModel,
            'foreignKey' => $foreignKey ?: $this->getForeignKey(),
            'localKey' => $localKey ?: $this->getPrimaryKey(),
            'instance' => $instance
        ];
    }

    /**
     * Define a one-to-one relationship
     */
    public function hasOne(string $relatedModel, ?string $foreignKey = null, ?string $localKey = null)
    {
        $instance = $this->resolveRelationInstance($relatedModel);
        return [
            'type' => 'hasOne',
            'model' => $relatedModel,
            'foreignKey' => $foreignKey ?: $this->getForeignKey(),
            'localKey' => $localKey ?: $this->getPrimaryKey(),
            'instance' => $instance
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship
     */
    public function belongsTo(string $relatedModel, ?string $foreignKey = null, ?string $ownerKey = null)
    {
        $instance = $this->resolveRelationInstance($relatedModel);
        return [
            'type' => 'belongsTo',
            'model' => $relatedModel,
            'foreignKey' => $foreignKey ?: $instance->getForeignKey(),
            // B-02: `$instance->primaryKey` kırılgan korumalı erişime dayanıyordu.
            // Kardeş model sınıfları arasında PHP erişime İZİN VERİYOR (alanı
            // declare eden BaseModel ortak atası) — yani raporun "Cannot access
            // protected property" iddiası YANLIŞTIR — ama public erişimci aynı
            // değeri verir ve model sınıfı değişse de kırılmaz.
            'ownerKey' => $ownerKey ?: $instance->getPrimaryKey(),
            'instance' => $instance
        ];
    }

    /**
     * Calculate default foreign key name based on model name
     */
    public function getForeignKey(): string
    {
        $name = (new \ReflectionClass($this))->getShortName();
        $name = str_replace('Model', '', $name);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . '_id';
    }

    /**
     * Eager Loading Core 🧠
     */
    public function loadRelationships(array $results, array $relations): array
    {
        if (empty($results))
            return $results;

        foreach ($relations as $relationName) {
            if (!method_exists($this, $relationName))
                continue;

            $relation = $this->$relationName();
            $ids = array_unique(array_column($results, $relation['localKey'] ?? $relation['ownerKey'] ?? $this->getPrimaryKey()));

            if (empty($ids))
                continue;

            $relatedResults = $relation['instance']->query()->whereIn($relation['foreignKey'], $ids)->get();

            $indexed = [];
            foreach ($relatedResults as $rel) {
                if ($relation['type'] === 'hasMany') {
                    $indexed[$rel[$relation['foreignKey']]][] = $rel;
                } else {
                    $indexed[$rel[$relation['foreignKey']]] = $rel;
                }
            }

            foreach ($results as &$row) {
                $row[$relationName] = $indexed[$row[$relation['localKey'] ?? $relation['ownerKey'] ?? $this->getPrimaryKey()]] ?? ($relation['type'] === 'hasMany' ? [] : null);
            }

            // B-75: `&$row` referansı çözülmeden sonraki döngüye geçilirse
            // `$row` hâlâ SON SATIRI gösterir ve sonraki ilişki ataması
            // yanlış satırı da ezer. Referansı burada kapatıyoruz.
            unset($row);
        }

        return $results;
    }
}
