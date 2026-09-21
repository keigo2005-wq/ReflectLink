package com.reflectlink.goal;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/goals")
public class GoalController {

    private static final List<String> ALLOWED_STATUSES = List.of("未着手", "取り組み中", "完了");

    private final GoalRepository repository;

    public GoalController(GoalRepository repository) {
        this.repository = repository;
    }

    @GetMapping
    public List<Goal> listByUser(@RequestParam Integer userId) {
        return repository.findByUserIdOrderByPeriodStartDesc(userId);
    }

    @PostMapping
    public ResponseEntity<Object> create(@RequestBody GoalRequest request) {
        if (request.getUserId() == null) {
            return badRequest("userId は必須です。");
        }
        if (request.getPeriodStart() == null || request.getPeriodEnd() == null) {
            return badRequest("期間(開始日・終了日)は必須です。");
        }
        if (request.getPeriodEnd().isBefore(request.getPeriodStart())) {
            return badRequest("終了日は開始日より後の日付にしてください。");
        }
        if (isBlank(request.getGoal())) {
            return badRequest("目標は必須です。");
        }
        if (isBlank(request.getAction())) {
            return badRequest("取り組む行動は必須です。");
        }

        Goal goal = new Goal();
        goal.setUserId(request.getUserId());
        goal.setPeriodStart(request.getPeriodStart());
        goal.setPeriodEnd(request.getPeriodEnd());
        goal.setGoal(request.getGoal());
        goal.setAction(request.getAction());

        Goal saved = repository.save(goal);
        return ResponseEntity.status(HttpStatus.CREATED).body(saved);
    }

    @PutMapping("/{id}")
    public ResponseEntity<Object> update(@PathVariable Long id, @RequestBody GoalRequest request) {
        if (request.getStatus() != null && !ALLOWED_STATUSES.contains(request.getStatus())) {
            return badRequest("statusは 未着手・取り組み中・完了 のいずれかにしてください。");
        }

        return repository.findById(id)
                .<ResponseEntity<Object>>map(existing -> {
                    if (request.getPeriodStart() != null) {
                        existing.setPeriodStart(request.getPeriodStart());
                    }
                    if (request.getPeriodEnd() != null) {
                        existing.setPeriodEnd(request.getPeriodEnd());
                    }
                    if (!isBlank(request.getGoal())) {
                        existing.setGoal(request.getGoal());
                    }
                    if (!isBlank(request.getAction())) {
                        existing.setAction(request.getAction());
                    }
                    if (request.getResult() != null) {
                        existing.setResult(request.getResult());
                    }
                    if (request.getStatus() != null) {
                        existing.setStatus(request.getStatus());
                    }
                    existing.setUpdatedAt(LocalDateTime.now());
                    return ResponseEntity.ok(repository.save(existing));
                })
                .orElseGet(() -> notFound());
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Object> delete(@PathVariable Long id) {
        if (!repository.existsById(id)) {
            return notFound();
        }
        repository.deleteById(id);
        return ResponseEntity.noContent().build();
    }

    private static boolean isBlank(String value) {
        return value == null || value.isBlank();
    }

    private static ResponseEntity<Object> badRequest(String message) {
        return ResponseEntity.status(HttpStatus.BAD_REQUEST).body(Map.of("error", message));
    }

    private static ResponseEntity<Object> notFound() {
        return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "指定された目標が見つかりません。"));
    }
}
