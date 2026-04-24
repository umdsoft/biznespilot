/**
 * useFunnelValidation - Validation layer for the Telegram Funnel Builder.
 *
 * Exports validateNodes(nodes) which returns:
 *   { valid: boolean, errors: { [nodeId]: string[] }, firstError: string|null }
 *
 * Rules:
 *  - Every message/input/start node must have non-empty text
 *  - Every quiz node must have >=2 options with non-empty text
 *  - Every ab_test node variants percentages must sum to 100
 *  - Every button with action=url must have valid http(s) URL
 *  - Every button with action=next_step must point to an existing node
 *  - Every subscribe_check must have non-empty channel_username matching handle regex
 *  - No orphan nodes (no incoming connections AND not a start/trigger)
 *  - No dangling next_step_id pointing to deleted nodes
 *  - No circular references (cycles) following next_step_id / branches
 */

const URL_REGEX = /^https?:\/\/[^\s]+$/i;
const CHANNEL_REGEX = /^@?[a-zA-Z0-9_]{5,}$/;

function pushErr(errors, nodeId, message) {
    if (!errors[nodeId]) errors[nodeId] = [];
    errors[nodeId].push(message);
}

function collectOutgoingTargets(node) {
    const out = [];
    if (node.type === 'condition') {
        if (node.condition_true_step_id) out.push(node.condition_true_step_id);
        if (node.condition_false_step_id) out.push(node.condition_false_step_id);
    } else if (node.type === 'subscribe_check') {
        if (node.subscribe_true_step_id) out.push(node.subscribe_true_step_id);
        if (node.subscribe_false_step_id) out.push(node.subscribe_false_step_id);
    } else if (node.type === 'quiz') {
        (node.quiz?.options || []).forEach((o) => {
            if (o?.next_step_id) out.push(o.next_step_id);
        });
    } else if (node.type === 'ab_test') {
        (node.ab_test?.variants || []).forEach((v) => {
            if (v?.next_step_id) out.push(v.next_step_id);
        });
    } else if (node.next_step_id) {
        out.push(node.next_step_id);
    }

    // Also consider inline-keyboard buttons that point to next_step
    const rows = node.keyboard?.buttons || [];
    rows.forEach((row) => {
        (row || []).forEach((btn) => {
            if (btn?.action_type === 'next_step' && btn?.next_step_id) {
                out.push(btn.next_step_id);
            }
        });
    });

    return out;
}

function hasCycle(nodes) {
    const byId = new Map(nodes.map((n) => [n.id, n]));
    const WHITE = 0,
        GRAY = 1,
        BLACK = 2;
    const color = new Map(nodes.map((n) => [n.id, WHITE]));
    const cyclicNodes = new Set();

    function dfs(id) {
        color.set(id, GRAY);
        const node = byId.get(id);
        if (!node) {
            color.set(id, BLACK);
            return false;
        }
        const targets = collectOutgoingTargets(node);
        for (const tId of targets) {
            const c = color.get(tId);
            if (c === GRAY) {
                cyclicNodes.add(id);
                cyclicNodes.add(tId);
                return true;
            }
            if (c === WHITE) {
                if (dfs(tId)) {
                    cyclicNodes.add(id);
                    return true;
                }
            }
        }
        color.set(id, BLACK);
        return false;
    }

    for (const n of nodes) {
        if (color.get(n.id) === WHITE) {
            dfs(n.id);
        }
    }
    return cyclicNodes;
}

export function validateNodes(nodes) {
    const errors = {};
    const nodeIds = new Set((nodes || []).map((n) => n.id));

    if (!Array.isArray(nodes) || nodes.length === 0) {
        return {
            valid: false,
            errors: {},
            firstError: "Funnel bo'sh — kamida bitta qadam qo'shing",
        };
    }

    // Track which nodes receive an incoming edge (for orphan detection)
    const hasIncoming = new Set();

    nodes.forEach((node) => {
        collectOutgoingTargets(node).forEach((tId) => hasIncoming.add(tId));
    });

    nodes.forEach((node) => {
        const t = node.type;

        // Text-content rule
        if (['message', 'input', 'start'].includes(t)) {
            const text = (node.content?.text || node.content?.caption || '').trim();
            if (!text) {
                pushErr(errors, node.id, 'Xabar matni bo\'sh bo\'lmasligi kerak');
            }
        }

        // Quiz rule
        if (t === 'quiz') {
            const opts = node.quiz?.options || [];
            if (opts.length < 2) {
                pushErr(errors, node.id, 'Quiz uchun kamida 2 ta variant kerak');
            }
            const emptyOption = opts.some((o) => !(o?.text || '').trim());
            if (emptyOption) {
                pushErr(errors, node.id, 'Quiz variantlari bo\'sh bo\'lmasligi kerak');
            }
            if (!(node.quiz?.question || '').trim()) {
                pushErr(errors, node.id, 'Quiz savoli kiritilmagan');
            }
        }

        // A/B test rule
        if (t === 'ab_test') {
            const variants = node.ab_test?.variants || [];
            const sum = variants.reduce(
                (acc, v) => acc + (Number(v?.percentage) || 0),
                0
            );
            if (sum !== 100) {
                pushErr(
                    errors,
                    node.id,
                    `A/B test foizlari yig'indisi 100 bo'lishi kerak (hozir ${sum})`
                );
            }
        }

        // Subscribe check rule
        if (t === 'subscribe_check') {
            const username = (node.subscribe_check?.channel_username || '').trim();
            if (!username) {
                pushErr(errors, node.id, 'Kanal username kiritilmagan');
            } else if (!CHANNEL_REGEX.test(username)) {
                pushErr(
                    errors,
                    node.id,
                    'Kanal username noto\'g\'ri formatda (@channel_name)'
                );
            }
        }

        // Button rules
        const rows = node.keyboard?.buttons || [];
        rows.forEach((row, rIdx) => {
            (row || []).forEach((btn, bIdx) => {
                if (!btn) return;
                if (btn.action_type === 'url') {
                    const url = (btn.url || '').trim();
                    if (!url || !URL_REGEX.test(url)) {
                        pushErr(
                            errors,
                            node.id,
                            `Tugma (${rIdx + 1}.${bIdx + 1}): URL noto'g'ri (http/https)`
                        );
                    }
                }
                if (btn.action_type === 'next_step') {
                    const nsId = btn.next_step_id;
                    if (!nsId) {
                        pushErr(
                            errors,
                            node.id,
                            `Tugma (${rIdx + 1}.${bIdx + 1}): keyingi qadam tanlanmagan`
                        );
                    } else if (!nodeIds.has(nsId)) {
                        pushErr(
                            errors,
                            node.id,
                            `Tugma (${rIdx + 1}.${bIdx + 1}): bog'langan qadam mavjud emas`
                        );
                    }
                }
            });
        });

        // Dangling references (targets pointing to non-existent nodes)
        const outgoing = collectOutgoingTargets(node);
        outgoing.forEach((tId) => {
            if (!nodeIds.has(tId)) {
                pushErr(errors, node.id, 'Bog\'langan qadam mavjud emas (dangling)');
            }
        });

        // Orphan nodes: not start/trigger and no incoming edges
        const isEntry = t === 'start' || t === 'trigger_keyword' || node.is_first === true;
        if (!isEntry && !hasIncoming.has(node.id)) {
            pushErr(errors, node.id, 'Orfan qadam: kirish ulanishi yo\'q');
        }
    });

    // Cycles
    const cyclic = hasCycle(nodes);
    cyclic.forEach((id) => {
        pushErr(errors, id, 'Aylanma ulanish (circular reference) aniqlandi');
    });

    const errorNodeIds = Object.keys(errors);
    const valid = errorNodeIds.length === 0;
    let firstError = null;
    if (!valid) {
        const firstId = errorNodeIds[0];
        const nodeName =
            (nodes.find((n) => n.id === firstId) || {}).name || 'Qadam';
        firstError = `${nodeName}: ${errors[firstId][0]}`;
    }

    return { valid, errors, firstError };
}

export default validateNodes;
