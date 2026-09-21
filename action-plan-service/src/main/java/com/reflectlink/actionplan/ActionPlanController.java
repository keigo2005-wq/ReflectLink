package com.reflectlink.actionplan;

import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/action-plans")
public class ActionPlanController {

    private final ActionPlanRepository repository;

    public ActionPlanController(ActionPlanRepository repository) {
        this.repository = repository;
    }

    @GetMapping
    public List<ActionPlan> listByPost(@RequestParam Integer postId) {
        return repository.findByPostIdOrderByCreatedAtDesc(postId);
    }

    @PostMapping
    public ResponseEntity<ActionPlan> create(@Valid @RequestBody CreateActionPlanRequest request) {
        ActionPlan plan = new ActionPlan();
        plan.setPostId(request.getPostId());
        plan.setAction(request.getAction());
        plan.setPracticeMenu(request.getPracticeMenu());
        plan.setDueDate(request.getDueDate());
        plan.setCreatedByName(request.getCreatedByName());

        ActionPlan saved = repository.save(plan);
        return ResponseEntity.status(HttpStatus.CREATED).body(saved);
    }

    @PatchMapping("/{id}/status")
    public ResponseEntity<Object> updateStatus(@PathVariable Long id, @Valid @RequestBody UpdateStatusRequest request) {
        return repository.findById(id)
                .<ResponseEntity<Object>>map(plan -> {
                    plan.setStatus(request.getStatus());
                    return ResponseEntity.ok(repository.save(plan));
                })
                .orElseGet(() -> ResponseEntity.status(HttpStatus.NOT_FOUND)
                        .body(Map.of("error", "指定された行動計画が見つかりません。")));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable Long id) {
        if (!repository.existsById(id)) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND)
                    .body(Map.of("error", "指定された行動計画が見つかりません。"));
        }
        repository.deleteById(id);
        return ResponseEntity.noContent().build();
    }
}
