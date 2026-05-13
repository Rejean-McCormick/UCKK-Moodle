<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Local uncertainty detector for governed UCKK AI actions.
 *
 * This class performs deterministic pre/post-analysis around AI-assisted text.
 * It does not call an AI model, does not grade, does not validate integrity,
 * does not close cases, and does not publish decisions.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class uncertainty_detector {
    /** Low uncertainty risk. */
    public const RISK_LOW = 'low';

    /** Medium uncertainty risk. */
    public const RISK_MEDIUM = 'medium';

    /** High uncertainty risk. */
    public const RISK_HIGH = 'high';

    /** Critical uncertainty risk. */
    public const RISK_CRITICAL = 'critical';

    /** Factual uncertainty category. */
    public const CATEGORY_FACTUAL = 'factual_uncertainty';

    /** Source/provenance uncertainty category. */
    public const CATEGORY_SOURCE = 'source_uncertainty';

    /** Methodological uncertainty category. */
    public const CATEGORY_METHOD = 'method_uncertainty';

    /** Interpretation/speculation uncertainty category. */
    public const CATEGORY_INTERPRETATION = 'interpretation_uncertainty';

    /** Integrity-sensitive uncertainty category. */
    public const CATEGORY_INTEGRITY = 'integrity_uncertainty';

    /** Privacy-sensitive uncertainty category. */
    public const CATEGORY_PRIVACY = 'privacy_uncertainty';

    /** Authority/decision uncertainty category. */
    public const CATEGORY_AUTHORITY = 'authority_uncertainty';

    /** Contradiction category. */
    public const CATEGORY_CONTRADICTION = 'contradiction_uncertainty';

    /** Default max number of uncertainty items returned. */
    private const DEFAULT_MAX_ITEMS = 50;

    /** Default excerpt radius in characters. */
    private const DEFAULT_EXCERPT_RADIUS = 160;

    /**
     * Marker library.
     *
     * @var array<string,array<int,array<string,mixed>>>
     */
    private const MARKERS = [
        self::CATEGORY_FACTUAL => [
            ['pattern' => '/\b(maybe|perhaps|possibly|probably|apparently|seems?|appears?|likely|unlikely)\b/iu', 'weight' => 18],
            ['pattern' => '/\b(unclear|unknown|not sure|cannot confirm|unverified|unconfirmed)\b/iu', 'weight' => 28],
            ['pattern' => '/\b(peut-être|probablement|semble|semblerait|apparemment|incertain|inconnu|non confirmé|non vérifié)\b/iu', 'weight' => 28],
            ['pattern' => '/\b(always|never|everyone|nobody|guaranteed|certainly|undeniably)\b/iu', 'weight' => 14],
            ['pattern' => '/\b(toujours|jamais|tout le monde|personne|garanti|certainement|indéniablement)\b/iu', 'weight' => 14],
        ],
        self::CATEGORY_SOURCE => [
            ['pattern' => '/\b(source needed|citation needed|no source|without source|unsourced|according to someone)\b/iu', 'weight' => 35],
            ['pattern' => '/\b(source requise|citation requise|sans source|non sourcé|selon quelqu.?un)\b/iu', 'weight' => 35],
            ['pattern' => '/\b(I heard|people say|they say|on the internet|rumour|rumor)\b/iu', 'weight' => 30],
            ['pattern' => '/\b(j.?ai entendu|on dit|les gens disent|sur internet|rumeur)\b/iu', 'weight' => 30],
        ],
        self::CATEGORY_METHOD => [
            ['pattern' => '/\b(assume|assumption|estimated|estimate|approximation|roughly|sample size|methodology)\b/iu', 'weight' => 22],
            ['pattern' => '/\b(suppose|supposition|estimé|estimation|approximatif|environ|taille de l.?échantillon|méthodologie)\b/iu', 'weight' => 22],
            ['pattern' => '/\b(correlation|causation|causal|bias|confounder|representative)\b/iu', 'weight' => 24],
            ['pattern' => '/\b(corrélation|causalité|biais|facteur de confusion|représentatif)\b/iu', 'weight' => 24],
        ],
        self::CATEGORY_INTERPRETATION => [
            ['pattern' => '/\b(opinion|interpretation|speculation|hypothesis|narrative|story|symbolic)\b/iu', 'weight' => 20],
            ['pattern' => '/\b(opinion|interprétation|spéculation|hypothèse|récit|symbolique)\b/iu', 'weight' => 20],
            ['pattern' => '/\b(may mean|could mean|suggests that|implies that)\b/iu', 'weight' => 18],
            ['pattern' => '/\b(pourrait signifier|suggère que|implique que)\b/iu', 'weight' => 18],
        ],
        self::CATEGORY_INTEGRITY => [
            ['pattern' => '/\b(grade|grading|sanction|punishment|integrity violation|cheating|plagiarism|fabricated evidence)\b/iu', 'weight' => 42],
            ['pattern' => '/\b(note|notation|sanction|punition|atteinte à l.?intégrité|tricherie|plagiat|preuve fabriquée)\b/iu', 'weight' => 42],
            ['pattern' => '/\b(invalidate|invalidated|validate integrity|close the case|final judgment)\b/iu', 'weight' => 48],
            ['pattern' => '/\b(invalider|invalidé|valider l.?intégrité|fermer le dossier|jugement final)\b/iu', 'weight' => 48],
        ],
        self::CATEGORY_PRIVACY => [
            ['pattern' => '/\b(private data|personal data|sensitive data|doxx|doxxing|identity|medical|legal|financial)\b/iu', 'weight' => 45],
            ['pattern' => '/\b(données privées|données personnelles|données sensibles|doxx|doxxing|identité|médical|juridique|financier)\b/iu', 'weight' => 45],
            ['pattern' => '/\b(email address|phone number|home address|student record|case note)\b/iu', 'weight' => 38],
            ['pattern' => '/\b(adresse courriel|numéro de téléphone|adresse personnelle|dossier étudiant|note de dossier)\b/iu', 'weight' => 38],
        ],
        self::CATEGORY_AUTHORITY => [
            ['pattern' => '/\b(final decision|must decide|official decision|award the badge|certify|publish decision)\b/iu', 'weight' => 44],
            ['pattern' => '/\b(décision finale|doit décider|décision officielle|attribuer le badge|certifier|publier la décision)\b/iu', 'weight' => 44],
            ['pattern' => '/\b(AI decided|AI confirms|AI proves|AI validates|AI certifies)\b/iu', 'weight' => 52],
            ['pattern' => '/\b(l.?IA a décidé|l.?IA confirme|l.?IA prouve|l.?IA valide|l.?IA certifie)\b/iu', 'weight' => 52],
        ],
        self::CATEGORY_CONTRADICTION => [
            ['pattern' => '/\b(however|but|although|nevertheless|on the other hand|contradicts|conflicts with)\b/iu', 'weight' => 18],
            ['pattern' => '/\b(cependant|mais|bien que|néanmoins|d.?autre part|contredit|entre en conflit avec)\b/iu', 'weight' => 18],
        ],
    ];

    /**
     * Human-readable recommendations per category.
     *
     * @var array<string,string>
     */
    private const RECOMMENDATIONS = [
        self::CATEGORY_FACTUAL => 'Verify the factual claim against evidence before reuse.',
        self::CATEGORY_SOURCE => 'Attach a source, archive item, citation, or provenance record.',
        self::CATEGORY_METHOD => 'Clarify assumptions, method, sample, limits, and confidence.',
        self::CATEGORY_INTERPRETATION => 'Label this as interpretation, hypothesis, or narrative reading.',
        self::CATEGORY_INTEGRITY => 'Send this to human integrity review; AI must not validate or sanction.',
        self::CATEGORY_PRIVACY => 'Review privacy, redaction, visibility, and logging settings before sending or publishing.',
        self::CATEGORY_AUTHORITY => 'Keep this as a non-authoritative draft; a human or valid UCKK workflow must decide.',
        self::CATEGORY_CONTRADICTION => 'Check whether the statement contradicts another claim, source, or decision record.',
    ];

    /**
     * Analyze text and return uncertainty findings.
     *
     * @param string $text Text to inspect.
     * @param array<string,mixed> $options Optional settings:
     *   - maxitems: int
     *   - includelowconfidence: bool
     *   - sensitivity: low|normal|high
     *   - excerptchars: int
     * @return array<string,mixed>
     */
    public static function detect(string $text, array $options = []): array {
        $options = self::normalize_options($options);
        $text = trim($text);

        if ($text === '') {
            return [
                'risklevel' => self::RISK_LOW,
                'score' => 0,
                'count' => 0,
                'items' => [],
                'categories' => [],
                'label' => self::non_authority_label(),
            ];
        }

        $sentences = self::split_sentences($text);
        $items = [];

        foreach ($sentences as $index => $sentence) {
            $sentenceitems = self::inspect_sentence($sentence, $index, $options);
            foreach ($sentenceitems as $item) {
                $items[] = $item;
            }
        }

        $items = self::deduplicate_items($items);
        usort($items, static function(array $a, array $b): int {
            return ($b['score'] <=> $a['score']) ?: strcmp($a['category'], $b['category']);
        });

        if (!$options['includelowconfidence']) {
            $items = array_values(array_filter($items, static function(array $item): bool {
                return $item['score'] >= 18;
            }));
        }

        $items = array_slice($items, 0, $options['maxitems']);
        $score = self::aggregate_score($items);
        $categories = self::category_counts($items);

        return [
            'risklevel' => self::risk_level($score),
            'score' => $score,
            'count' => count($items),
            'items' => $items,
            'categories' => $categories,
            'label' => self::non_authority_label(),
        ];
    }

    /**
     * Return only the uncertainty score for text.
     *
     * @param string $text Text to inspect.
     * @param array<string,mixed> $options Optional detector options.
     * @return int Score from 0 to 100.
     */
    public static function score(string $text, array $options = []): int {
        $result = self::detect($text, $options);

        return (int)$result['score'];
    }

    /**
     * Return true when text should be routed to human review.
     *
     * @param string $text Text to inspect.
     * @param array<string,mixed> $options Optional detector options.
     * @return bool
     */
    public static function requires_human_review(string $text, array $options = []): bool {
        $result = self::detect($text, $options);

        return in_array($result['risklevel'], [self::RISK_HIGH, self::RISK_CRITICAL], true);
    }

    /**
     * Return the non-authority label required on AI-assisted uncertainty output.
     *
     * @param string $lang Language code.
     * @return string
     */
    public static function non_authority_label(string $lang = 'en'): string {
        if (strpos($lang, 'fr') === 0) {
            return 'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';
        }

        return 'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';
    }

    /**
     * Convert detector result into prompt-safe summary text.
     *
     * @param array<string,mixed> $result Detector result returned by detect().
     * @return string
     */
    public static function summarize_result(array $result): string {
        $lines = [];
        $lines[] = (string)($result['label'] ?? self::non_authority_label());
        $lines[] = 'Risk level: ' . (string)($result['risklevel'] ?? self::RISK_LOW);
        $lines[] = 'Score: ' . (string)($result['score'] ?? 0);
        $lines[] = 'Findings: ' . (string)($result['count'] ?? 0);

        foreach (($result['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $lines[] = '- [' . ($item['risklevel'] ?? self::RISK_LOW) . '] '
                . ($item['category'] ?? 'unknown') . ': '
                . ($item['excerpt'] ?? '');
        }

        return implode("\n", $lines);
    }

    /**
     * Normalize caller options.
     *
     * @param array<string,mixed> $options Raw options.
     * @return array<string,mixed>
     */
    private static function normalize_options(array $options): array {
        $sensitivity = clean_param((string)($options['sensitivity'] ?? 'normal'), PARAM_ALPHA);
        if (!in_array($sensitivity, ['low', 'normal', 'high'], true)) {
            $sensitivity = 'normal';
        }

        return [
            'maxitems' => max(1, min(250, (int)($options['maxitems'] ?? self::DEFAULT_MAX_ITEMS))),
            'includelowconfidence' => !empty($options['includelowconfidence']),
            'sensitivity' => $sensitivity,
            'excerptchars' => max(60, min(800, (int)($options['excerptchars'] ?? self::DEFAULT_EXCERPT_RADIUS))),
        ];
    }

    /**
     * Split text into sentences.
     *
     * @param string $text Text.
     * @return string[]
     */
    private static function split_sentences(string $text): array {
        $normalized = preg_replace('/\s+/u', ' ', trim($text));
        if ($normalized === null || $normalized === '') {
            return [];
        }

        $sentences = preg_split('/(?<=[.!?。！？])\s+/u', $normalized);
        if ($sentences === false || count($sentences) === 0) {
            return [$normalized];
        }

        return array_values(array_filter(array_map('trim', $sentences), static function(string $sentence): bool {
            return $sentence !== '';
        }));
    }

    /**
     * Inspect a sentence for uncertainty signals.
     *
     * @param string $sentence Sentence text.
     * @param int $sentenceindex Sentence index.
     * @param array<string,mixed> $options Normalized options.
     * @return array<int,array<string,mixed>>
     */
    private static function inspect_sentence(string $sentence, int $sentenceindex, array $options): array {
        $items = [];

        foreach (self::MARKERS as $category => $markers) {
            foreach ($markers as $marker) {
                $matches = [];
                if (!preg_match_all($marker['pattern'], $sentence, $matches, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches[0] as $match) {
                    $markertext = (string)$match[0];
                    $offset = (int)$match[1];
                    $score = self::adjust_score((int)$marker['weight'], $options['sensitivity']);

                    $items[] = [
                        'id' => sha1($category . ':' . $sentenceindex . ':' . $offset . ':' . $markertext),
                        'category' => $category,
                        'risklevel' => self::risk_level($score),
                        'score' => $score,
                        'confidence' => self::confidence_from_score($score),
                        'marker' => $markertext,
                        'sentenceindex' => $sentenceindex,
                        'excerpt' => self::excerpt($sentence, $offset, $options['excerptchars']),
                        'recommendation' => self::RECOMMENDATIONS[$category],
                    ];
                }
            }
        }

        $items = array_merge($items, self::inspect_structural_risks($sentence, $sentenceindex, $options));

        return $items;
    }

    /**
     * Inspect structural risks that are not tied to one marker word.
     *
     * @param string $sentence Sentence text.
     * @param int $sentenceindex Sentence index.
     * @param array<string,mixed> $options Normalized options.
     * @return array<int,array<string,mixed>>
     */
    private static function inspect_structural_risks(string $sentence, int $sentenceindex, array $options): array {
        $items = [];

        if (self::looks_like_absolute_claim($sentence) && !self::has_evidence_marker($sentence)) {
            $score = self::adjust_score(26, $options['sensitivity']);
            $items[] = self::structural_item(
                self::CATEGORY_SOURCE,
                $sentenceindex,
                $sentence,
                $score,
                'absolute_claim_without_source',
                'Attach evidence or soften the absolute claim.'
            );
        }

        if (self::looks_like_decision_claim($sentence)) {
            $score = self::adjust_score(46, $options['sensitivity']);
            $items[] = self::structural_item(
                self::CATEGORY_AUTHORITY,
                $sentenceindex,
                $sentence,
                $score,
                'decision_language',
                self::RECOMMENDATIONS[self::CATEGORY_AUTHORITY]
            );
        }

        if (self::looks_like_sensitive_context($sentence)) {
            $score = self::adjust_score(40, $options['sensitivity']);
            $items[] = self::structural_item(
                self::CATEGORY_PRIVACY,
                $sentenceindex,
                $sentence,
                $score,
                'sensitive_context',
                self::RECOMMENDATIONS[self::CATEGORY_PRIVACY]
            );
        }

        return $items;
    }

    /**
     * Build a structural uncertainty item.
     *
     * @param string $category Category.
     * @param int $sentenceindex Sentence index.
     * @param string $sentence Sentence.
     * @param int $score Score.
     * @param string $marker Marker.
     * @param string $recommendation Recommendation.
     * @return array<string,mixed>
     */
    private static function structural_item(
        string $category,
        int $sentenceindex,
        string $sentence,
        int $score,
        string $marker,
        string $recommendation
    ): array {
        return [
            'id' => sha1($category . ':' . $sentenceindex . ':' . $marker . ':' . $sentence),
            'category' => $category,
            'risklevel' => self::risk_level($score),
            'score' => $score,
            'confidence' => self::confidence_from_score($score),
            'marker' => $marker,
            'sentenceindex' => $sentenceindex,
            'excerpt' => self::excerpt($sentence, 0, self::DEFAULT_EXCERPT_RADIUS),
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Deduplicate findings.
     *
     * @param array<int,array<string,mixed>> $items Items.
     * @return array<int,array<string,mixed>>
     */
    private static function deduplicate_items(array $items): array {
        $seen = [];
        $deduped = [];

        foreach ($items as $item) {
            $key = ($item['category'] ?? '') . ':' . ($item['sentenceindex'] ?? '') . ':' . ($item['marker'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $item;
        }

        return $deduped;
    }

    /**
     * Aggregate item scores into a capped risk score.
     *
     * @param array<int,array<string,mixed>> $items Items.
     * @return int Score 0-100.
     */
    private static function aggregate_score(array $items): int {
        if (empty($items)) {
            return 0;
        }

        $score = 0;
        foreach ($items as $item) {
            $score += max(0, (int)($item['score'] ?? 0));
        }

        $diversitybonus = count(self::category_counts($items)) * 4;

        return min(100, (int)round(($score / max(1, count($items))) + $diversitybonus));
    }

    /**
     * Count findings by category.
     *
     * @param array<int,array<string,mixed>> $items Items.
     * @return array<string,int>
     */
    private static function category_counts(array $items): array {
        $counts = [];

        foreach ($items as $item) {
            $category = (string)($item['category'] ?? 'unknown');
            $counts[$category] = ($counts[$category] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /**
     * Convert score to risk level.
     *
     * @param int $score Score.
     * @return string
     */
    private static function risk_level(int $score): string {
        if ($score >= 75) {
            return self::RISK_CRITICAL;
        }

        if ($score >= 50) {
            return self::RISK_HIGH;
        }

        if ($score >= 25) {
            return self::RISK_MEDIUM;
        }

        return self::RISK_LOW;
    }

    /**
     * Convert score to detector confidence.
     *
     * @param int $score Score.
     * @return float
     */
    private static function confidence_from_score(int $score): float {
        return round(min(0.99, max(0.1, $score / 100)), 2);
    }

    /**
     * Adjust score according to sensitivity.
     *
     * @param int $score Base score.
     * @param string $sensitivity Sensitivity.
     * @return int
     */
    private static function adjust_score(int $score, string $sensitivity): int {
        if ($sensitivity === 'high') {
            return min(100, (int)round($score * 1.25));
        }

        if ($sensitivity === 'low') {
            return max(1, (int)round($score * 0.75));
        }

        return $score;
    }

    /**
     * Return a safe excerpt around a marker.
     *
     * @param string $text Text.
     * @param int $offset Offset.
     * @param int $radius Excerpt radius.
     * @return string
     */
    private static function excerpt(string $text, int $offset, int $radius): string {
        $length = \core_text::strlen($text);
        $start = max(0, $offset - $radius);
        $excerptlength = min($length - $start, ($radius * 2));

        $excerpt = \core_text::substr($text, $start, $excerptlength);

        if ($start > 0) {
            $excerpt = '…' . $excerpt;
        }

        if (($start + $excerptlength) < $length) {
            $excerpt .= '…';
        }

        return trim($excerpt);
    }

    /**
     * Detect absolute claim style.
     *
     * @param string $sentence Sentence.
     * @return bool
     */
    private static function looks_like_absolute_claim(string $sentence): bool {
        return (bool)preg_match(
            '/\b(all|always|never|none|everyone|nobody|proves|certain|undeniable|'
            . 'tous|toujours|jamais|aucun|prouve|certain|indéniable)\b/iu',
            $sentence
        );
    }

    /**
     * Detect evidence markers.
     *
     * @param string $sentence Sentence.
     * @return bool
     */
    private static function has_evidence_marker(string $sentence): bool {
        return (bool)preg_match(
            '/\b(source|citation|evidence|archive|proof|data|according to|'
            . 'preuve|donnée|données|selon|archive|citation)\b/iu',
            $sentence
        );
    }

    /**
     * Detect decision/authority language.
     *
     * @param string $sentence Sentence.
     * @return bool
     */
    private static function looks_like_decision_claim(string $sentence): bool {
        return (bool)preg_match(
            '/\b(AI should decide|AI decides|final authority|award badge|grade this|validate this|'
            . 'l.?IA devrait décider|l.?IA décide|autorité finale|attribuer le badge|noter ceci|valider ceci)\b/iu',
            $sentence
        );
    }

    /**
     * Detect sensitive context language.
     *
     * @param string $sentence Sentence.
     * @return bool
     */
    private static function looks_like_sensitive_context(string $sentence): bool {
        return (bool)preg_match(
            '/\b(integrity case|restricted|private note|personal data|student record|'
            . 'dossier d.?intégrité|restreint|note privée|données personnelles|dossier étudiant)\b/iu',
            $sentence
        );
    }
}